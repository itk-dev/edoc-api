<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Util;

abstract class ItemListType
{
    public const ACCESS_PARAGRAPH = 'AccessParagraph';
    public const AGREEMENT = 'Agreement';
    public const ARCHIVE_FORMAT = 'ArchiveFormat';
    public const CASE_CATEGORY = 'CaseCategory';
    public const CASE_FILE_STATUS = 'CaseFileStatus';
    public const CASE_PARTICIPANT_TYPE = 'CaseParticipantType';
    public const CASE_STATE = 'CaseState';
    public const CASE_TEMPLATE = 'CaseTemplate';
    public const CASE_TYPE = 'CaseType';
    public const CASE_WORKER = 'CaseWorker';
    public const DISCARD_CODE = 'DiscardCode';
    public const DOCUMENT_ARCHIVE = 'DocumentArchive';
    public const DOCUMENT_CATEGORY_CODE = 'DocumentCategoryCode';
    public const DOCUMENT_STATUS_CODE = 'DocumentStatusCode';
    public const DOCUMENT_TYPE = 'DocumentType';
    public const EXTERN_SYSTEM = 'ExternSystem';
    public const FAVORITE_CODE = 'FavoriteCode';
    public const FILE_VARIANT = 'FileVariant';
    public const HANDLING_CODE_TREE = 'HandlingCodeTree';
    public const JOURNAL_NOTE_TYPE = 'JournalNoteType';
    public const OCCUPATION = 'Occupation';
    public const ORGANISATION = 'Organisation';
    public const PRIMARY_CODE_TREE = 'PrimaryCodeTree';
    public const PROJECT = 'Project';
    public const PUBLIC_ACCESS_CODE = 'PublicAccessCode';
    public const SECURITY_CODE_TREE = 'SecurityCodeTree';
    public const TRANSFER_TO_RECORD_PERIOD = 'TransferToRecordPeriod';
    public const USER_SECURITY_CODE_TREE = 'UserSecurityCodeTree';
    public const USER_VISIBLE_CODE_TREE = 'UserVisibleCodeTree';
    public const WORK_REPORT = 'WorkReport';

    public static function getValues()
    {
        $class = new \ReflectionClass(self::class);
        $constants = $class->getConstants();
        $values = array_values($constants);
        sort($values);

        return $values;
    }
}
