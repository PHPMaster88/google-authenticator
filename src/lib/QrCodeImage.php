<?php
/*
 * This file is part of the PHPMaster88 Project package.
 *
 * (c) John Yusuf Habila <Senestro88@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPMaster88\authenticator\lib;

use \chillerlan\QRCode\QROptions as chillerlanQROptions;
use \chillerlan\QRCode\QRCode as chillerlanQRCode;

class QrCodeImage{
    /**
     * Generates a URL that is used to show a QR code.
     *
     * @param string $accountName The account name to show and identify
     * @param string $secret The secret is the generated secret unique to that user
     * @param string|null $issuer      Where you log in to
     * @param int $size Image size in pixels, 200 will make it 200x200
     */
    public static function generate(string $accountName, string $secret, ?string $issuer = null): string {
        if ('' === $accountName || false !== strpos($accountName, ':')) {throw QrException::InvalidAccountName($accountName); }
        if ('' === $secret) {throw QrException::InvalidSecret(); }

        $label = $accountName;
        $otpauthString = 'otpauth://totp/%s?secret=%s';

        if (null !== $issuer) {
            if ('' === $issuer || false !== strpos($issuer, ':')) {throw QrException::InvalidIssuer($issuer); }
            // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
            $label = $issuer.':'.$label;
            $otpauthString .= '&issuer=%s';
        }

        $otpauthString = htmlspecialchars_decode(sprintf($otpauthString, $label, $secret, $issuer));

        // $qrOptions = new chillerlanQROptions(array('eccLevel' => chillerlanQRCode::ECC_Q, 'outputType' => chillerlanQRCode::OUTPUT_IMAGE_PNG, 'version' => 5));
        // return (new chillerlanQRCode)->render($otpauthString);

        $currentPath = trim(rtrim(str_replace("\\", "/", realpath(dirname(__FILE__))), "/")); $prevPath = realpath($currentPath."\/../");
        if(!defined('QR_MODE_NUL')){require_once $currentPath."/phpqrcode/qrlib.php";}
        $tmpPath = $prevPath."/temp/"; if(!is_dir($tmpPath)){@mkdir($tmpPath, 0777, true);}
        $Filename = $tmpPath.''.md5(time()).'.png';
        touch($Filename);
        \QRcode::png($otpauthString, $Filename, QR_ECLEVEL_Q, 4, 2);
        try {
            clearstatcache();
            $mime = mime_content_type($Filename);
            $baseEncode = base64_encode((string) @file_get_contents($Filename));
            return "data:".$mime.";base64,".$baseEncode;
        } catch (\Exception $e) {} finally {clearstatcache(); @unlink($Filename);}
    }
}