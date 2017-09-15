<?php

declare(strict_types=1);

namespace App\Http\Services;

use Exception;

class BdecodingService
{
    /**
     * The encoded string
     *
     * @var string
     */
    private $encodedString;

    /**
     * The length of the encoded source string
     *
     * @var integer
     */
    private $length;

    /**
     * The current position of the parser.
     *
     * @var integer
     */
    private $position;

    /**
     * @param string $data
     * @return array|int|string
     */
    public function decode(string $data)
    {
        $this->encodedString = $data;
        $this->length = strlen($data);
        $this->position = 0;

        return $this->doDecode();
    }

    /**
     * @return array|int|string
     * @throws Exception
     */
    private function doDecode()
    {
        switch ($this->getCharacterAtPosition()) {
            case "i":
                $this->position++;
                return $this->decodeInteger();
            case "l":
                $this->position++;
                return $this->decodeList();
            case "d":
                $this->position++;
                return $this->decodeDictionary();
            default:
                if (ctype_digit($this->getCharacterAtPosition())) {
                    return $this->decodeString();
                }
        }

        throw new Exception("Unknown type found at position $this->position");
    }

    /**
     * @param int|null $position
     * @return null|string
     */
    private function getCharacterAtPosition(?int $position = null): ?string
    {
        if (null === $position) {
            $position = $this->position;
        }

        if (empty($this->encodedString) || $this->position >= $this->length) {
            return null;
        }

        return $this->encodedString[$position];
    }

    /**
     * Note: this function should return an integer but
     * the int return type hint is not specified because
     * on 32 bit systems it would overflow
     * @return int|float
     * @throws Exception
     */
    private function decodeInteger()
    {
        $positionOfIntegerEndingDelimiter = strpos($this->encodedString, "e", $this->position);
        if (false === $positionOfIntegerEndingDelimiter) {
            throw new Exception("Integer value does not have an ending delimiter at position $this->position");
        }

        $currentPosition = $this->position;
        if ("-" === $this->getCharacterAtPosition($currentPosition)) {
            $currentPosition++;
        }

        if ($positionOfIntegerEndingDelimiter === $currentPosition) {
            throw new Exception("Empty integer value at position $this->position");
        }

        while ($currentPosition < $positionOfIntegerEndingDelimiter) {
            if (!ctype_digit($this->getCharacterAtPosition($currentPosition))) {
                throw new Exception("Non-numeric character found in an integer value at position $this->position");
            }

            $currentPosition++;
        }

        $decodedValue = substr(
            $this->encodedString,
            $this->position,
            $positionOfIntegerEndingDelimiter - $this->position
        );
        // Check for zero-padded integers
        if (strlen($decodedValue) > 1 && "0" === $decodedValue[0]) {
            throw new Exception("Illegal zero-padding for an integer value found at position $this->position");
        }

        $this->position = $positionOfIntegerEndingDelimiter + 1;

        $floatValue = (float) $decodedValue;

        if (4 === constant('PHP_INT_SIZE') && ($floatValue > 2147483647 || $floatValue < -2147483647)) {
            return $floatValue;
        }
        return (int) $decodedValue;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function decodeString(): string
    {
        if ("0" === $this->getCharacterAtPosition() && ":" !== $this->getCharacterAtPosition($this->position + 1)) {
            throw new Exception("Illegal zero-padding for a string length declaration at position $this->position");
        }

        $positionOfColon = strpos($this->encodedString, ":", $this->position);
        if (false === $positionOfColon) {
            throw new Exception("The string value at position $this->position does not have a colon");
        }

        $contentLength = (int) substr($this->encodedString, $this->position, $positionOfColon);
        if (($contentLength + $positionOfColon + 1) > $this->length) {
            throw new Exception("Unexpected end of a string value at position $this->position");
        }

        $decodedStringValue = substr($this->encodedString, $positionOfColon + 1, $contentLength);
        $this->position = $positionOfColon + $contentLength + 1;

        return $decodedStringValue;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function decodeList(): array
    {
        $decodedList = [];
        $endingDelimiter = false;
        $listPosition = $this->position;
        while (null !== $this->getCharacterAtPosition()) {
            if ("e" === $this->getCharacterAtPosition()) {
                $endingDelimiter = true;
                break;
            }

            $decodedList[] = $this->doDecode();
        }

        if (false === $endingDelimiter && null === $this->getCharacterAtPosition()) {
            throw new Exception("A list does not have an ending delimiter at position $listPosition");
        }

        $this->position++;

        return $decodedList;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function decodeDictionary(): array
    {
        $dictionary = [];
        $endingDelimiter = false;
        $dictionaryPosition = $this->position;
        while (null !== $this->getCharacterAtPosition()) {
            if ("e" === $this->getCharacterAtPosition()) {
                $endingDelimiter = true;
                break;
            }

            $keyPosition = $this->position;
            if (!ctype_digit($this->getCharacterAtPosition())) {
                throw new Exception("A dictionary contains an invalid key at position $keyPosition");
            }

            $key = $this->decodeString();
            if (isset($dictionary[$key])) {
                throw new Exception("A dictionary contains a duplicate key at position $keyPosition");
            }

            $dictionary[$key] = $this->doDecode();
        }

        if (false === $endingDelimiter && null === $this->getCharacterAtPosition()) {
            throw new Exception("A dictionary does not have an ending delimiter at position $dictionaryPosition");
        }

        $this->position++;

        return $dictionary;
    }
}
