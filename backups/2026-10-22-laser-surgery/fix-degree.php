<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/host1510/public_html";
$_SERVER["HTTP_HOST"] = "kravira.by";
$_SERVER["SERVER_NAME"] = "kravira.by";
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
CModule::IncludeModule("iblock");

foreach ([21114, 4624] as $id) {
    $html = file_get_contents("/tmp/laser-style/" . $id . "/activity.html");
    if ($html === false || strpos($html, "—") !== false || strpos($html, "кандидат медицинских наук") === false) {
        fwrite(STDERR, "bad html $id\n");
        exit(1);
    }
    CIBlockElement::SetPropertyValuesEx($id, 4, [
        "ACTIVITY" => ["VALUE" => ["TEXT" => $html, "TYPE" => "HTML"]],
    ]);
    echo "set $id " . strlen($html) . "\n";
}
if (isset($GLOBALS["CACHE_MANAGER"])) {
    $GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_4");
}
CIBlock::clearIblockTagCache(4);
BXClearCache(true);
echo "done\n";
