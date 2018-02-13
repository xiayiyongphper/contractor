<?php
namespace service\resources;

use service\models\common\CustomerException;

class Exception extends CustomerException
{
    const VISIT_RECORD_OUT_OF_EDITABLE_TIME = 13005;
    const VISIT_RECORD_OUT_OF_EDITABLE_TIME_TEXT = '拜访记录已过可拜访时间';
    const VISIT_RECORD_NOT_FOUND = 13006;
    const VISIT_RECORD_NOT_FOUND_TEXT = '拜访记录不存在';

    public static function visitRecordOutOfEditableTime()
    {
        throw new \Exception(self::VISIT_RECORD_OUT_OF_EDITABLE_TIME_TEXT, self::VISIT_RECORD_OUT_OF_EDITABLE_TIME);
    }

    public static function visitRecordNotFound()
    {
        throw new \Exception(self::VISIT_RECORD_NOT_FOUND_TEXT, self::VISIT_RECORD_NOT_FOUND);
    }
}