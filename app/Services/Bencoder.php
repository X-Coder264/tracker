<?php

declare(strict_types=1);

namespace App\Services;

class Bencoder
{
    /**
     * @param mixed $data
     *
     * @return string|null
     */
    public function encode($data): ?string
    {
        if (is_array($data) && (isset($data[0]) || empty($data))) {
            return $this->encodeList($data);
        } elseif (is_array($data)) {
            return $this->encodeDictionary($data);
        } elseif (is_int($data) || is_float($data)) {
            return $this->encodeInteger($data);
        } elseif (is_string($data)) {
            return $this->encodeString($data);
        }

        return null;
    }

    /**
     * Encode an integer.
     *
     * @param int|float $data
     *
     * @return string
     */
    private function encodeInteger($data): string
    {
        return sprintf('i%.0fe', $data);
    }

    /**
     * Encode a string.
     *
     * @param  string
     *
     * @return string
     */
    private function encodeString(string $data): string
    {
        return sprintf('%d:%s', strlen($data), $data);
    }

    /**
     * Encode a list.
     *
     * @param  array
     *
     * @return string
     */
    private function encodeList(array $data = []): string
    {
        $list = '';
        foreach ($data as $value) {
            $list .= $this->encode($value);
        }

        return sprintf('l%se', $list);
    }

    /**
     * Encode a dictionary.
     *
     * @param array $info
     *
     * @return string
     */
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
