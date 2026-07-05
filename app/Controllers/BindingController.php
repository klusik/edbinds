<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Repositories\BindingRepository;
use App\Services\AuthService;
use App\Services\BindingUploadService;

/**
 * Binding upload, dashboard, card view and original XML download controller.
 */
final class BindingController
{
    /**
     * Show the authenticated user's bindings.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $auth = new AuthService();
        $user = $auth->requireUser();
        $repo = new BindingRepository();

        View::render('bindings/dashboard', [
            'title' => 'My bindings',
            'user' => $user,
            'sets' => $repo->ownedSets($user->id),
        ]);
    }

    /**
     * Upload a new binding set or append a version.
     *
     * @return void
     */
    public function upload(): void
    {
        $auth = new AuthService();
        $user = $auth->requireUser();
        $repo = new BindingRepository();
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireValidPost();
            try {
                $versionData = (new BindingUploadService())->prepareUploadedVersion($_FILES['binds_file'] ?? [], trim((string)($_POST['change_note'] ?? '')));
                $mode = (string)($_POST['mode'] ?? 'new');

                if ($mode === 'version') {
                    $setId = (int)($_POST['binding_set_id'] ?? 0);
                    if ($setId <= 0 || !$repo->canUpdateSet($setId, $user)) {
                        throw new \RuntimeException('You cannot add a version to this binding set.');
                    }
                    $repo->addVersion($setId, $user->id, $versionData);
                    Session::flash('success', 'New version uploaded.');
                    Response::redirect('index.php?page=view&id=' . $setId);
                }

                $title = trim((string)($_POST['title'] ?? ''));
                if ($title === '') {
                    $title = (string)($versionData['parsed']['metadata']['preset_name'] ?? 'Elite bindings');
                }
                $visibility = (string)($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';
                $description = trim((string)($_POST['description'] ?? ''));
                $setId = $repo->createSetWithVersion($user->id, $title, $description, $visibility, $versionData);
                Session::flash('success', 'Binding file uploaded.');
                Response::redirect('index.php?page=view&id=' . $setId);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        View::render('bindings/upload', [
            'title' => 'Upload bindings',
            'user' => $user,
            'sets' => $repo->ownedSets($user->id),
            'error' => $error,
        ]);
    }

    /**
     * Show a binding card.
     *
     * @return void
     */
    public function view(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $repo = new BindingRepository();
        $versionId = (int)($_GET['version'] ?? 0);
        $row = $versionId > 0 ? $repo->findVisibleVersion($versionId, $user) : $repo->findVisibleSet((int)($_GET['id'] ?? 0), $user);

        if (!$row) {
            Response::notFound();
        }

        $parsed = json_decode((string)$row['parsed_json'], true, 512, JSON_THROW_ON_ERROR);
        View::render('bindings/view', [
            'title' => (string)$row['title'],
            'user' => $user,
            'binding' => $row,
            'parsed' => $parsed,
            'versions' => $repo->versionsForSet((int)$row['id']),
        ]);
    }

    /**
     * Download the original XML for a visible version.
     *
     * @return void
     */
    public function download(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $repo = new BindingRepository();
        $row = $repo->findVisibleVersion((int)($_GET['version'] ?? 0), $user);
        if (!$row) {
            Response::notFound();
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string)$row['original_filename']) . '"');
        echo (string)$row['xml_text'];
    }
}
