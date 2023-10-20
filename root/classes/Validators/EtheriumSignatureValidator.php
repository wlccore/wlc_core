<?php

namespace eGamings\WLC\Validators;

use Elliptic\EC;
use kornrunner\Keccak;

class EtheriumSignatureValidator extends AbstractValidator
{

    public function validate($value, $params, $data, $field)
    {
        if (empty($data['message']) || empty($data['signature']) || empty($data['walletAddress'])) {
            return false;
        }

        $msglen = strlen($data['message']);
        $hash = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$data['message']}", 256);
        $sign = [
            "r" => substr($data['signature'], 2, 64),
            "s" => substr($data['signature'], 66, 64)
        ];
        $recid = ord(hex2bin(substr($data['signature'], 130, 2))) - 27;
        if ($recid != ($recid & 1)) {
            return false;
        }

        $ec = new EC('secp256k1');
        $pubkey = $ec->recoverPubKey($hash, $sign, $recid);

        return $data['walletAddress'] === $this->pubKeyToAddress($pubkey);
    }

    private function pubKeyToAddress($pubkey): string
    {
        return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
    }
}
