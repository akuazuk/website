<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/host1510/public_html";
$_SERVER["HTTP_HOST"] = "kravira.by";
$_SERVER["SERVER_NAME"] = "kravira.by";
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
CModule::IncludeModule("iblock");

$payload = json_decode(file_get_contents("/tmp/urology.json"), true);
if (!$payload) {
    fwrite(STDERR, "bad json\n");
    exit(1);
}

function prop_text($id, $code) {
    $r = CIBlockElement::GetProperty(4, $id, ["sort" => "asc"], ["CODE" => $code]);
    $parts = [];
    while ($row = $r->Fetch()) {
        $val = $row["VALUE"];
        if (is_array($val)) {
            $val = isset($val["TEXT"]) ? $val["TEXT"] : "";
        }
        if ($val !== "" && $val !== null) {
            $parts[] = $val;
        }
    }
    return implode("\n", $parts);
}

function set_html($id, $code, $html) {
    CIBlockElement::SetPropertyValuesEx((int)$id, 4, [
        $code => ["VALUE" => ["TEXT" => $html, "TYPE" => "HTML"]],
    ]);
}

$backupDir = "/home/host1510/backups/urology-2026-10-21";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$backup = [];
foreach ($payload["doctors"] as $id => $fields) {
    $backup[$id] = [];
    foreach (["ACTIVITY", "EDUCATION", "AWARDS", "CONFERENCES"] as $code) {
        $backup[$id][$code] = prop_text($id, $code);
    }
}
file_put_contents($backupDir . "/before.json", json_encode($backup, JSON_UNESCAPED_UNICODE));
echo "backup " . strlen(file_get_contents($backupDir . "/before.json")) . "\n";

foreach ($payload["doctors"] as $id => $fields) {
    foreach (["ACTIVITY", "EDUCATION", "AWARDS", "CONFERENCES"] as $code) {
        if (!isset($fields[$code]) || $fields[$code] === "") {
            continue;
        }
        if (strpos($fields[$code], "—") !== false) {
            fwrite(STDERR, "em dash in $id $code\n");
            exit(1);
        }
        set_html($id, $code, $fields[$code]);
    }
    echo "set $id\n";
}

foreach ($payload["dash_only"] as $id) {
    $text = prop_text($id, "ACTIVITY");
    $text = str_replace("—", "–", $text);
    $text = preg_replace('/<span\s+strong="">(.*?)<\/span>/s', '$1', $text);
    set_html($id, "ACTIVITY", $text);
    echo "dash $id em=" . substr_count($text, "—") . " en=" . substr_count($text, "–") . "\n";
}

if (isset($GLOBALS["CACHE_MANAGER"])) {
    $GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_4");
}
BXClearCache(true);
if (class_exists("\\Bitrix\\Main\\Data\\StaticHtmlCache")) {
    $static = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
    if (method_exists($static, "deleteAll")) {
        $static->deleteAll();
    }
}
echo "done\n";
