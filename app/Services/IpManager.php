<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidIpException;
use App\Presenters\Ip;

class IpManager
{
    protected const PORT_VALUE = [
        'min' => 1,
        'max' => 65535
    ];

    protected const IPv4 = FILTER_FLAG_IPV4;
    protected const IPv6 = FILTER_FLAG_IPV6;

    /**
     * Checks if provided string is a valid IPv4.
     */
    public function isV4(string $ip): bool
    {
        return $this->is($ip, static::IPv4);
    }

    /**
     * Checks if provided string is a valid IPv6.
     */
    public function isV6(string $ip): bool
    {
        return $this->is($ip, static::IPv6);
    }

    protected function is(string $ip, int $type): bool
    {
        if(false === filter_var($ip, FILTER_VALIDATE_IP, $type)){
            return false;
        }

        return true;
    }

    /**
     * Checks if port is valid.
     */
    public function isPortValid(int $port): bool
    {
        return $port >= static::PORT_VALUE['min'] && $port <= static::PORT_VALUE['max'];
    }

    /**
     * Creates IPv4 object from string, if port exists in $ip string,
     * it will override provided argument $port.
     *
     * @throws InvalidIpException
     */
    public function convertV4StringToIp(string $ip, ?int $port = null): Ip
    {
        $explodedIpString = explode(':', $ip);
        // check if the ipv4 field has the IP address and the port

        if (2 === count($explodedIpString)) {
            $ip = $explodedIpString[0];
            $port = (int)$explodedIpString[1];
        }

        if (!$this->isV4($ip)) {
            throw InvalidIpException::badIpV4();
        }

        return $this->makeIpV4($ip, $port);
    }

    /**
     * Creates IPv6 object from string, if port exists in $ip string,
     * it will override provided argument $port.
     *
     * @throws InvalidIpException
     */
    public function convertV6StringToIp(string $ip, ?int $port = null): Ip
    {
        $explodedIpString = explode(':', $ip);
        // check if the ipv6 field has the IP address and the port

        if (4 <= count($explodedIpString) && '[' === $ip[0] && false !== strpos($ip, ']')) {
            $ipWithPort = str_replace(['[',']'], '', $ip);
            $ip = substr($ipWithPort, 0, strrpos($ipWithPort, ':'));
            $port = (int) substr($ipWithPort, strrpos($ipWithPort, ':') + 1);
        }

        if(!$this->isV6($ip)){
            throw InvalidIpException::badIpV6();
        }

        return $this->makeIpV6($ip, $port);
    }

    /**
     * Creates IPv4 from provided $ip and $port.
     */
    public function makeIpV4(string $ip, ?int $port): Ip
    {
        if (null !== $port){
            $port = $this->isPortValid($port) ? $port : null;
        }

        if (!$this->isV4($ip)){
            throw InvalidIpException::badIpV4();
        }

        return new Ip($ip, $port, true);
    }

    /**
     * Creates IPv6 from provided $ip and $port.
     */
    public function makeIpV6(string $ip, ?int $port): Ip
    {
        if (null !== $port){
            $port = $this->isPortValid($port) ? $port : null;
        }

        if (!$this->isV6($ip)){
            throw InvalidIpException::badIpV6();
        }

        return new Ip($ip, $port, false);
    }

    /**
     * Tries to decide if provided IP is a v4 or v6 and creates IP object.
     *
     * @throws InvalidIpException
     */
    public function make(string $ip, ?int $port): Ip
    {
        if($this->isV4($ip)){
            return $this->makeIpV4($ip, $port);
        }

        if($this->isV6($ip)){
            return $this->makeIpV6($ip, $port);
        }

        throw InvalidIpException::badIp();
    }
}
