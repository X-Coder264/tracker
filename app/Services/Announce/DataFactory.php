<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Enumerations\AnnounceEvent;
use App\Exceptions\AnnounceValidationException;
use App\Presenters\Announce\Data;
use App\Presenters\Announce\DataValidator;
use App\Presenters\Ip;
use App\Services\IpManager;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;

class DataFactory
{
    private $dataValidator;
    /**
     * @var IpManager
     */
    private $ipManager;
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(
        DataValidator $dataValidator,
        IpManager $ipManager,
        Translator $translator
    )
    {
        $this->dataValidator = $dataValidator;
        $this->ipManager = $ipManager;
        $this->translator = $translator;
    }

    /**
     * @throws AnnounceValidationException
     */
    public function makeFromRequest(Request $request): Data
    {
        $fields = [
            'event', 'passkey', 'info_hash', 'peer_id',
            'ip', 'port', 'ipv4', 'ipv6', 'uploaded', 'left', 'compact', 'numwant',
            'downloaded', 'uploaded'
        ];

        $data = [];

        foreach ($fields as $fieldName){
            if($request->has($fieldName)){
                $data[$fieldName] = $request->input($fieldName);
            }
        }

        $data['user_agent'] = $request->userAgent();
        $data['client_ip'] = $request->getClientIp();

        return $this->makeFromArray($data);
    }

    /**
     * @throws AnnounceValidationException
     */
    public function makeFromArray(array $data): Data
    {
        $this->dataValidator->validate($data);

        if(!isset($data['event'])){
            $data['event'] = AnnounceEvent::PING;
        }

        $ips = $this->extractIpData($data);

        $numberOfWantedPeers = null;
        if(isset($data['numwant'])){
            $numberOfWantedPeers = (int)$data['numwant'];
        }

        // return compact response if the client wants a compact response or if the client did not
        // specify what kind of response it wants, else return non-compact response
        $compact = true;
        if(isset($data['compact']) && 0 === (int)$data['compact']){
            $compact = false;
        }

        return new Data(
            $data['event'],
            $data['passkey'],
            $data['user_agent'],
            $data['info_hash'],
            $data['peer_id'],
            (int)$data['downloaded'],
            (int)$data['uploaded'],
            (int)$data['left'],
            $compact,
            $numberOfWantedPeers,
            $ips['v4'],
            $ips['v6']
        );
    }

    /**
     * @throws AnnounceValidationException
     */
    protected function extractIpData(array $data): array
    {
        $ip = [
            'v4' => null,
            'v6' => null
        ];

        if($data['event'] === AnnounceEvent::STOPPED){
            return $ip;
        }

        // in order to support IPv6 peers (BEP 7) a more complex IP validation logic is needed

        $port = (int)$data['port'];

        $ips = [
            'v4' => null,
            'v6' => null,
        ];

        if (!empty($data['ip'])){
            $requestIp = $this->ipManager->make($data['ip'], $port);
            if($requestIp->isV4()){
                $ips['v4'] = $requestIp;
            }else{
                $ips['v6'] = $requestIp;
            }
        }

        if (!empty($data['ipv4'])){
            $ips['v4'] = $this->ipManager->convertV4StringToIp($data['ipv4'], $port);
        }

        if (!empty($data['ipv6'])){
            $ips['v6'] = $this->ipManager->convertV6StringToIp($data['ipv6'], $port);
        }

        // this is the most secure way to get the real IP address because for example
        // uTorrent with Teredo enabled sends only an "IPv6" address even though the peer
        // has actually only an IPv4 address
        $clientIp = $data['client_ip'];

        if($this->ipManager->isV4($clientIp)){
            $ips['v4'] = $this->ipManager->makeIpV4(
                $clientIp,
                $ips['v4'] instanceof Ip ? $ips['v4']->getPort() : $port
            );
        }

        if($this->ipManager->isV6($clientIp)){
            $ips['v6'] = $this->ipManager->makeIpV6(
                $clientIp,
                $ips['v6'] instanceof Ip ? $ips['v6']->getPort() : $port
            );
        }

        if(empty($ips['v4']) && empty($ips['v6'])){
            // throw the validation exception if there is not at least one IP address and port set
            throw new AnnounceValidationException($this->translator->trans('messages.announce.invalid_ip_or_port'));
        }

        return $ips;
    }
}
