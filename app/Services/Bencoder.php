<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

class Bencoder
{
    public function encode($data): string
    {
        if (is_array($data) && (isset($data[0]) || empty($data))) {
            return $this->encodeList($data);
        } elseif (is_array($data)) {
            return $this->encodeDictionary($data);
        } elseif (is_int($data)) {
            return $this->encodeInteger($data);
        } elseif (is_string($data)) {
            return $this->encodeString($data);
        }

        throw new Exception('Invalid data given for encoding.');
    }

    private function encodeInteger(int $data): string
    {
        return sprintf('i%de', $data);
    }

    private function encodeString(string $data): string
    {
        return sprintf('%d:%s', strlen($data), $data);
    }

    private function encodeList(array $data = []): string
    {
        $list = '';
        foreach ($data as $value) {
            $list .= $this->encode($value);
        }

        return sprintf('l%se', $list);
    }

    private function encodeDictionary(array $info = []): string
    {
        // keys must be strings and appear in sorted order (sorted as raw strings, not alphanumerics)
        ksort($info, SORT_STRING);
        $dictionary = '';
        foreach ($info as $key => $value) {
            $dictionary .= $this->encodeString($key) . $this->encode($value);
        }

        return sprintf('d%se', $dictionary);
    }
}
