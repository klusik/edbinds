<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

/**
 * Database access for binding sets and immutable versions.
 */
final class BindingRepository
{
    private PDO $pdo;

    /**
     * Create the repository.
     */
    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Return public binding sets.
     *
     * @return array<int,array<string,mixed>> Public binding rows.
     */
    public function publicSets(): array
    {
        $sql = 'SELECT s.*, u.username, v.version_number, v.created_at AS version_created_at, v.stats_json
                FROM binding_sets s
                JOIN users u ON u.id = s.owner_user_id
                LEFT JOIN binding_versions v ON v.id = s.active_version_id
                WHERE s.visibility = "public" AND s.deleted_at IS NULL
                ORDER BY s.updated_at DESC
                LIMIT 100';
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Return binding sets visible in the admin table.
     *
     * @return array<int,array<string,mixed>> Binding rows.
     */
    public function allSetsForAdmin(): array
    {
        $sql = 'SELECT s.*, u.username, v.version_number, v.created_at AS version_created_at, v.stats_json
                FROM binding_sets s
                JOIN users u ON u.id = s.owner_user_id
                LEFT JOIN binding_versions v ON v.id = s.active_version_id
                WHERE s.deleted_at IS NULL
                ORDER BY s.updated_at DESC
                LIMIT 200';
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Return binding sets owned by one user.
     *
     * @param int $userId Owner user id.
     * @return array<int,array<string,mixed>> Binding rows.
     */
    public function ownedSets(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, v.version_number, v.created_at AS version_created_at, v.stats_json
             FROM binding_sets s
             LEFT JOIN binding_versions v ON v.id = s.active_version_id
             WHERE s.owner_user_id = ? AND s.deleted_at IS NULL
             ORDER BY s.updated_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a binding set visible to a user or to anonymous visitors.
     *
     * @param int $id Binding set id.
     * @param User|null $user Current user.
     * @return array<string,mixed>|null Binding set with active version.
     */
    public function findVisibleSet(int $id, ?User $user): ?array
    {
        $params = [$id];
        $accessSql = 's.visibility = "public"';
        if ($user instanceof User) {
            $accessSql .= ' OR s.owner_user_id = ?';
            $params[] = $user->id;
            if ($user->isAdmin()) {
                $accessSql .= ' OR 1 = 1';
            }
        }

        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.username, v.id AS version_id, v.version_number, v.original_filename, v.file_hash,
                    v.normalized_hash, v.xml_text, v.parsed_json, v.stats_json, v.change_note,
                    v.created_at AS version_created_at
             FROM binding_sets s
             JOIN users u ON u.id = s.owner_user_id
             JOIN binding_versions v ON v.id = s.active_version_id
             WHERE s.id = ? AND s.deleted_at IS NULL AND (' . $accessSql . ')
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a specific binding version visible to a user.
     *
     * @param int $versionId Binding version id.
     * @param User|null $user Current user.
     * @return array<string,mixed>|null Version row.
     */
    public function findVisibleVersion(int $versionId, ?User $user): ?array
    {
        $params = [$versionId];
        $accessSql = 's.visibility = "public"';
        if ($user instanceof User) {
            $accessSql .= ' OR s.owner_user_id = ?';
            $params[] = $user->id;
            if ($user->isAdmin()) {
                $accessSql .= ' OR 1 = 1';
            }
        }

        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.username, v.id AS version_id, v.version_number, v.original_filename, v.file_hash,
                    v.normalized_hash, v.xml_text, v.parsed_json, v.stats_json, v.change_note,
                    v.created_at AS version_created_at
             FROM binding_versions v
             JOIN binding_sets s ON s.id = v.binding_set_id
             JOIN users u ON u.id = s.owner_user_id
             WHERE v.id = ? AND s.deleted_at IS NULL AND (' . $accessSql . ')
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Return all versions for a binding set.
     *
     * @param int $setId Binding set id.
     * @return array<int,array<string,mixed>> Version rows.
     */
    public function versionsForSet(int $setId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, version_number, original_filename, file_hash, normalized_hash, stats_json, change_note, created_at
             FROM binding_versions WHERE binding_set_id = ? ORDER BY version_number DESC'
        );
        $stmt->execute([$setId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new binding set with its first version.
     *
     * @param int $ownerUserId Owner id.
     * @param string $title Display title.
     * @param string $description Description.
     * @param string $visibility public or private.
     * @param array<string,mixed> $versionData Version payload.
     * @return int New binding set id.
     */
    public function createSetWithVersion(int $ownerUserId, string $title, string $description, string $visibility, array $versionData): int
    {
        $this->pdo->beginTransaction();
        try {
            $slug = $this->uniqueSlug($title);
            $stmt = $this->pdo->prepare(
                'INSERT INTO binding_sets (owner_user_id, title, slug, description, visibility, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$ownerUserId, $title, $slug, $description, $visibility]);
            $setId = (int)$this->pdo->lastInsertId();

            $versionId = $this->insertVersion($setId, $ownerUserId, 1, $versionData);
            $stmt = $this->pdo->prepare('UPDATE binding_sets SET active_version_id = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$versionId, $setId]);

            $this->pdo->commit();
            return $setId;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Add a new immutable version to an existing binding set.
     *
     * @param int $setId Binding set id.
     * @param int $uploadedByUserId Uploader id.
     * @param array<string,mixed> $versionData Version payload.
     * @return int New version id.
     */
    public function addVersion(int $setId, int $uploadedByUserId, array $versionData): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_number), 0) + 1 FROM binding_versions WHERE binding_set_id = ?');
            $stmt->execute([$setId]);
            $versionNumber = (int)$stmt->fetchColumn();
            $versionId = $this->insertVersion($setId, $uploadedByUserId, $versionNumber, $versionData);

            $stmt = $this->pdo->prepare('UPDATE binding_sets SET active_version_id = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$versionId, $setId]);

            $this->pdo->commit();
            return $versionId;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Change binding visibility.
     *
     * @param int $setId Binding set id.
     * @param string $visibility New visibility.
     * @return void
     */
    public function updateVisibility(int $setId, string $visibility): void
    {
        $stmt = $this->pdo->prepare('UPDATE binding_sets SET visibility = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$visibility, $setId]);
    }

    /**
     * Determine whether a user can update a binding set.
     *
     * @param int $setId Binding set id.
     * @param User $user Current user.
     * @return bool True when update is allowed.
     */
    public function canUpdateSet(int $setId, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM binding_sets WHERE id = ? AND owner_user_id = ? AND deleted_at IS NULL');
        $stmt->execute([$setId, $user->id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Insert one version row.
     *
     * @param int $setId Binding set id.
     * @param int $uploadedByUserId Uploader id.
     * @param int $versionNumber Version number.
     * @param array<string,mixed> $versionData Version payload.
     * @return int New version id.
     */
    private function insertVersion(int $setId, int $uploadedByUserId, int $versionNumber, array $versionData): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO binding_versions
             (binding_set_id, version_number, uploaded_by_user_id, original_filename, file_hash, normalized_hash,
              xml_text, parsed_json, stats_json, change_note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $setId,
            $versionNumber,
            $uploadedByUserId,
            $versionData['original_filename'],
            $versionData['file_hash'],
            $versionData['normalized_hash'],
            $versionData['xml_text'],
            $versionData['parsed_json'],
            $versionData['stats_json'],
            $versionData['change_note'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Generate a unique URL slug.
     *
     * @param string $title Binding title.
     * @return string Unique slug.
     */
    private function uniqueSlug(string $title): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?: 'bindings', '-'));
        $base = $base !== '' ? $base : 'bindings';

        do {
            $slug = $base . '-' . bin2hex(random_bytes(4));
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM binding_sets WHERE slug = ?');
            $stmt->execute([$slug]);
        } while ((int)$stmt->fetchColumn() > 0);

        return $slug;
    }
}
