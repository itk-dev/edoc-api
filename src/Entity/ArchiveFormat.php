<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

use ReflectionClass;
use RuntimeException;

class ArchiveFormat extends Entity
{
    public const UF = 1;
    public const DOC = 2;
    public const XLS = 3;
    public const PPT = 4;
    public const MPP = 5;
    public const RTF = 6;
    public const TIF = 11;
    public const PDF = 12;
    public const TXT = 13;
    public const HTM = 14;
    public const JPG = 15;
    public const MSG = 16;
    public const DWF = 17;
    public const ZIP = 18;
    public const DWG = 20;
    public const ODT = 21;
    public const ODS = 22;
    public const ODG = 23;
    public const ODP = 24;
    public const XML = 25;
    public const DOCX = 26;
    public const EML = 27;
    public const MHT = 28;
    public const XLSX = 29;
    public const PPTX = 30;
    public const GIF = 31;
    public const ONE = 32;
    public const DOCM = 33;
    public const SOSI = 34;
    public const MPEG2 = 35;
    public const MP3 = 36;
    public const XLSB = 37;
    public const PPTM = 38;
    public const VSD = 39;
    public const VSDX = 40;
    public const KBL = 300004;
    public const OFT = 300018;
    public const JP2 = 300082;
    public const MP4 = 300083;
    public const MPG = 300084;
    // public const MP3 = 300085;
    public const GML = 300086;
    public const WAV = 300087;
    public const DPDF = 300089;
    public const LWP = 500002;
    public const UKNT = 500005;
    public const PNG = 500011;
    public const ADX = 500014;
    public const AID = 500015;
    public const AI = 500016;
    public const ISM = 500017;
    public const APR = 500018;
    public const AVL = 500019;
    public const BMP = 500020;
    public const CSV = 500021;
    public const DGN = 500022;
    public const DXF = 500023;
    public const EMF = 500024;
    public const EMZ = 500025;
    public const HTML = 500026;
    public const ERR = 500027;
    public const IND = 500028;
    public const KLD = 500029;
    public const KMZ = 500030;
    public const KOM = 500031;
    public const LNK = 500032;
    public const LOG = 500033;
    public const MAP = 500034;
    public const MDI = 500035;
    public const MER = 500036;
    public const MID = 500037;
    public const MIF = 500038;
    public const MOV = 500039;
    // public const MP3 = 500040;
    // public const MP4 = 500041;
    // public const MPG = 500042;
    public const MWP = 500043;
    public const MPW = 500044;
    public const MSO = 500045;
    public const OPL = 500046;
    public const OPT = 500047;
    public const PKT = 500048;
    public const PMT = 500049;
    public const PPS = 500050;
    public const PPSX = 500051;
    // public const PPTM = 500052;
    public const PSD = 500053;
    public const PUB = 500054;
    public const RAR = 500055;
    public const TIFF = 500056;
    public const WMF = 500057;
    public const WMV = 500058;
    public const WPD = 500059;
    public const WPS = 500060;
    public const XLSM = 500061;

    public $Code;
    public $FileExtension;
    public $Mimetype;

    public static function getArchiveFormat(string $filename)
    {
        $ext = pathinfo(strtoupper($filename), \PATHINFO_EXTENSION);
        $class = new ReflectionClass(self::class);
        $constant = $class->getConstant($ext);

        if (false === $constant) {
            throw new RuntimeException(sprintf('Cannot get archive format for file %s', $filename));
        }

        return $constant;
    }

    protected function build(array $data)
    {
        $this->Code = $data['ArchiveFormatCode'] ?? $data['Code'];
        $this->FileExtension = $data['ArchiveFormatFileExtension'] ?? $data['FileExtension'];
        $this->Mimetype = $data['ArchiveFormatMimetype'] ?? $data['Mimetype'];
    }
}
