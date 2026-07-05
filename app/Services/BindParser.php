<?php

namespace App\Services;

/**
 * Parser for Elite Dangerous .binds XML files.
 */
final class BindParser
{
    private ControlDictionary $dictionary;

    /** @var array<string,string> */
    private const KEY_LABELS = [
        'Key_LeftControl' => 'Left Ctrl',
        'Key_RightControl' => 'Right Ctrl',
        'Key_LeftShift' => 'Left Shift',
        'Key_RightShift' => 'Right Shift',
        'Key_LeftAlt' => 'Left Alt',
        'Key_RightAlt' => 'Right Alt',
        'Key_Space' => 'Space',
        'Key_Return' => 'Enter',
        'Key_Enter' => 'Enter',
        'Key_Backspace' => 'Backspace',
        'Key_Delete' => 'Delete',
        'Key_Insert' => 'Insert',
        'Key_Home' => 'Home',
        'Key_End' => 'End',
        'Key_PageUp' => 'PgUp',
        'Key_PageDown' => 'PgDn',
        'Key_UpArrow' => 'Up',
        'Key_DownArrow' => 'Down',
        'Key_LeftArrow' => 'Left',
        'Key_RightArrow' => 'Right',
        'Key_Tab' => 'Tab',
        'Key_Escape' => 'Esc',
        'Key_Minus' => '-',
        'Key_Equals' => '=',
        'Key_Comma' => ',',
        'Key_Period' => '.',
        'Key_Slash' => '/',
        'Key_BackSlash' => '\\',
        'Key_SemiColon' => ';',
        'Key_Apostrophe' => 'Acute',
        'Key_Grave' => '`',
        'Key_LeftBracket' => '[',
        'Key_RightBracket' => ']',
        'Key_NumPad0' => 'Num 0',
        'Key_NumPad1' => 'Num 1',
        'Key_NumPad2' => 'Num 2',
        'Key_NumPad3' => 'Num 3',
        'Key_NumPad4' => 'Num 4',
        'Key_NumPad5' => 'Num 5',
        'Key_NumPad6' => 'Num 6',
        'Key_NumPad7' => 'Num 7',
        'Key_NumPad8' => 'Num 8',
        'Key_NumPad9' => 'Num 9',
        'Key_NumPadEnter' => 'Num Enter',
        'Key_NumPadPlus' => 'Num +',
        'Key_NumPadMinus' => 'Num -',
        'Key_NumPadMultiply' => 'Num *',
        'Key_NumPadDivide' => 'Num /',
        'Key_NumPadDecimal' => 'Num .',
    ];

    /**
     * Create the parser.
     */
    public function __construct()
    {
        $this->dictionary = new ControlDictionary();
    }

    /**
     * Parse a .binds XML string into a normalized card model.
     *
     * @param string $xml XML file content.
     * @param string $sourceName Original file name.
     * @return array<string,mixed> Parsed model.
     */
    public function parseString(string $xml, string $sourceName = 'bindings.binds'): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            throw new \InvalidArgumentException('The bindings file is empty.');
        }

        if (!preg_match('/<Root\b([^>]*)>(.*)<\/Root>/si', $xml, $rootMatch)) {
            throw new \InvalidArgumentException('Expected an Elite Dangerous Root XML element.');
        }

        $rootAttributes = $this->parseAttributes($rootMatch[1]);
        $rootBody = $rootMatch[2];
        $keyboardLayout = '';
        if (preg_match('/<KeyboardLayout>(.*?)<\/KeyboardLayout>/si', $rootBody, $layoutMatch)) {
            $keyboardLayout = html_entity_decode(trim($layoutMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $metadata = [
            'preset_name' => $rootAttributes['PresetName'] ?? 'Custom',
            'major_version' => $rootAttributes['MajorVersion'] ?? '',
            'minor_version' => $rootAttributes['MinorVersion'] ?? '',
            'keyboard_layout' => $keyboardLayout,
            'source_name' => $sourceName,
        ];

        $commands = [];
        $byCategory = [];
        $conflictIndex = [];

        preg_match_all('/<([A-Za-z0-9_]+)\b([^>]*)>(.*?)<\/\1>/s', $rootBody, $nodes, PREG_SET_ORDER);
        foreach ($nodes as $nodeMatch) {
            $command = $nodeMatch[1];
            if ($command === 'KeyboardLayout') {
                continue;
            }

            $bindings = $this->extractBindings($nodeMatch[3]);
            if (!$bindings) {
                continue;
            }

            $category = $this->dictionary->category($command);
            $item = [
                'command' => $command,
                'label' => $this->dictionary->label($command),
                'category' => $category,
                'bindings' => $bindings,
            ];
            $commands[] = $item;
            $byCategory[$category][] = $item;

            foreach ($bindings as $binding) {
                $key = (string)$binding['conflict_key'];
                $conflictIndex[$key][] = $item['label'];
            }
        }

        $categoryOrder = ['Ship', 'SRV', 'Scanners', 'Fighter', 'On Foot', 'Multicrew', 'UI', 'Galaxy Map', 'Camera', 'Holo-Me', 'Misc'];
        uksort($byCategory, static function (string $a, string $b) use ($categoryOrder): int {
            $ai = array_search($a, $categoryOrder, true);
            $bi = array_search($b, $categoryOrder, true);
            $ai = $ai === false ? 999 : $ai;
            $bi = $bi === false ? 999 : $bi;
            return $ai <=> $bi ?: strcasecmp($a, $b);
        });

        foreach ($byCategory as &$items) {
            usort($items, static fn (array $a, array $b): int => strcasecmp((string)$a['label'], (string)$b['label']));
        }
        unset($items);

        $conflicts = [];
        foreach ($conflictIndex as $combo => $labels) {
            $unique = array_values(array_unique($labels));
            if (count($unique) > 1) {
                $conflicts[] = ['combo' => $combo, 'commands' => $unique];
            }
        }

        $stats = [
            'commands_total' => count($commands),
            'categories_total' => count($byCategory),
            'bindings_total' => array_sum(array_map(static fn (array $item): int => count($item['bindings']), $commands)),
            'conflicts_total' => count($conflicts),
        ];

        return [
            'metadata' => $metadata,
            'stats' => $stats,
            'categories' => $byCategory,
            'commands' => $commands,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Normalize a parsed file for stable change hashes.
     *
     * @param array<string,mixed> $parsed Parsed model.
     * @return string Normalized JSON string.
     */
    public function normalizedJson(array $parsed): string
    {
        $copy = $parsed;
        unset($copy['metadata']['source_name']);
        return json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Extract bound Primary, Secondary and Binding slots from one command body.
     *
     * @param string $body XML body of one command node.
     * @return array<int,array<string,mixed>> Binding rows.
     */
    private function extractBindings(string $body): array
    {
        $bindings = [];
        preg_match_all('/<(Primary|Secondary|Binding)\b([^>]*?)(?:\/>|>(.*?)<\/\1>)/s', $body, $slots, PREG_SET_ORDER);

        foreach ($slots as $slotMatch) {
            $slot = $slotMatch[1];
            $attributes = $this->parseAttributes($slotMatch[2]);
            $device = $attributes['Device'] ?? '';
            $key = $attributes['Key'] ?? '';
            if (!$this->isUsableBinding($device, $key)) {
                continue;
            }

            $modifiers = [];
            $slotBody = $slotMatch[3] ?? '';
            preg_match_all('/<Modifier\b([^>]*?)\/>/s', $slotBody, $modifierMatches, PREG_SET_ORDER);
            foreach ($modifierMatches as $modifierMatch) {
                $modifierAttributes = $this->parseAttributes($modifierMatch[1]);
                $modifierDevice = $modifierAttributes['Device'] ?? '';
                $modifierKey = $modifierAttributes['Key'] ?? '';
                if ($this->isUsableBinding($modifierDevice, $modifierKey)) {
                    $modifiers[] = [
                        'device' => $modifierDevice,
                        'key' => $modifierKey,
                        'label' => $this->keyLabel($modifierKey, $modifierDevice),
                    ];
                }
            }

            $parts = array_map(static fn (array $modifier): string => (string)$modifier['label'], $modifiers);
            $parts[] = $this->keyLabel($key, $device);
            $combo = implode(' + ', $parts);
            $conflictKey = implode('+', array_map('strtolower', $parts));

            $bindings[] = [
                'slot' => $slot,
                'device' => $device,
                'key' => $key,
                'key_label' => $this->keyLabel($key, $device),
                'modifiers' => $modifiers,
                'combo' => $combo,
                'conflict_key' => $conflictKey,
                'is_keyboard' => $device === 'Keyboard',
            ];
        }

        return $bindings;
    }

    /**
     * Parse XML attributes from an opening tag fragment.
     *
     * @param string $source Attribute fragment.
     * @return array<string,string> Parsed attributes.
     */
    private function parseAttributes(string $source): array
    {
        $attributes = [];
        preg_match_all('/([A-Za-z0-9_:-]+)\s*=\s*("([^"]*)"|\'([^\']*)\')/s', $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $value = ($match[3] ?? '') !== '' ? $match[3] : ($match[4] ?? '');
            $attributes[$match[1]] = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
        return $attributes;
    }

    /**
     * Check if a device/key pair is an actual binding.
     *
     * @param string $device Device name.
     * @param string $key Key identifier.
     * @return bool True when usable.
     */
    private function isUsableBinding(string $device, string $key): bool
    {
        return $device !== '' && $device !== '{NoDevice}' && $key !== '';
    }

    /**
     * Convert an Elite key identifier to a compact label.
     *
     * @param string $key Key identifier.
     * @param string $device Device name.
     * @return string Readable label.
     */
    private function keyLabel(string $key, string $device): string
    {
        if (isset(self::KEY_LABELS[$key])) {
            return self::KEY_LABELS[$key];
        }

        if (preg_match('/^Key_([A-Z])$/', $key, $match)) {
            return $match[1];
        }

        if (preg_match('/^Key_([0-9])$/', $key, $match)) {
            return $match[1];
        }

        if (preg_match('/^Key_F([0-9]{1,2})$/', $key, $match)) {
            return 'F' . $match[1];
        }

        $label = preg_replace('/^(Key_|Mouse_|Joy_)/', '', $key) ?: $key;
        $label = str_replace('_', ' ', $label);
        if ($device !== 'Keyboard' && !str_starts_with($label, $device)) {
            $label = $device . ' ' . $label;
        }
        return trim($label);
    }
}
