<?php

declare(strict_types=1);

namespace App\Presenters;

use InvalidArgumentException;

class IpManager
{
    protected const PORT_VALUE = [
        'min' => 1,
        'max' => 65535
    ];

    protected const IPv4 = FILTER_FLAG_IPV4;
    protected const IPv6 = FILTER_FLAG_IPV6;

    public function isV4(string $ip): bool
    {
        return $this->is($ip, static::IPv4);
    }

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

    public function isPortValid(int $port): bool
    {
        return $port >= static::PORT_VALUE['min'] && $port <= static::PORT_VALUE['max'];
    }

    public function convertV4StringToIp(string $ip, ?int $port = null): Ip
    {
        $explodedIpString = explode(':', $ip);
        // check if the ipv4 field has the IP address and the port

        if (2 === count($explodedIpString)) {
            $ip = $explodedIpString[0];
            $port = (int)$explodedIpString[1];
        }

        if (!$this->isV4($ip)) {
            throw new InvalidArgumentException('Provided string is not ip v4');
        }

        return $this->makeIpV4($ip, $port);
    }

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
            throw new InvalidArgumentException('Provided string is not ip v6');
        }

        return $this->makeIpV6($ip, $port);
    }

    public function makeIpV4(string $ip, ?int $port): Ip
    {
        if (null !== $port){
            $port = $this->isPortValid($port) ? $port : null;
        }

        return new Ip($ip, $port, true);
    }

    public function makeIpV6(string $ip, ?int $port): Ip
    {
        if (null !== $port){
            $port = $this->isPortValid($port) ? $port : null;
        }

        return new Ip($ip, $port, false);
    }

    public function make(string $ip, ?int $port): Ip
    {
        if($this->isV4($ip)){
            return $this->makeIpV4($ip, $port);
        }

        if($this->isV6($ip)){
            return $this->makeIpV6($ip, $port);
        }

        throw new \InvalidArgumentException('Invalid Ip provided');
    }
}
