<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/host1510/public_html";
$_SERVER["HTTP_HOST"] = "kravira.by";
$_SERVER["SERVER_NAME"] = "kravira.by";
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
CModule::IncludeModule("iblock");

$ids = [21114, 1146, 15289, 1150, 4624, 7069, 17976, 12833, 12533];
$clearDetail = [1150, 7069];
$base = "/tmp/laser-style";
$backupDir = "/home/host1510/backups/2026-10-22-laser-surgery";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
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

$backup = [];
foreach ($ids as $id) {
    $res = CIBlockElement::GetList(
        [],
        ["ID" => $id, "IBLOCK_ID" => 4],
        false,
        false,
        ["ID", "NAME", "ACTIVE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE"]
    );
    $row = $res->Fetch();
    if (!$row) {
        fwrite(STDERR, "missing $id\n");
        exit(1);
    }
    if ($row["ACTIVE"] !== "Y") {
        fwrite(STDERR, "inactive $id {$row["NAME"]}\n");
        exit(1);
    }
    $backup[$id] = [
        "NAME" => $row["NAME"],
        "DETAIL_TEXT" => $row["DETAIL_TEXT"],
        "DETAIL_TEXT_TYPE" => $row["DETAIL_TEXT_TYPE"],
    ];
    foreach (["ACTIVITY", "EDUCATION", "AWARDS", "CONFERENCES"] as $code) {
        $backup[$id][$code] = prop_text($id, $code);
    }
    echo "backup $id " . $row["NAME"] . "\n";
}
$json = json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($backupDir . "/before.json", $json);
echo "backup bytes " . strlen($json) . "\n";

$map = [
    "activity.html" => "ACTIVITY",
    "education.html" => "EDUCATION",
    "awards.html" => "AWARDS",
    "conferences.html" => "CONFERENCES",
];
foreach ($ids as $id) {
    foreach ($map as $file => $code) {
        $path = $base . "/" . $id . "/" . $file;
        if (!is_file($path)) {
            continue;
        }
        $html = file_get_contents($path);
        if (strpos($html, "—") !== false) {
            fwrite(STDERR, "emdash $id $code\n");
            exit(1);
        }
        set_html($id, $code, $html);
        echo "set $id $code " . strlen($html) . "\n";
    }
}

$element = new CIBlockElement();
foreach ($clearDetail as $id) {
    $ok = $element->Update($id, [
        "DETAIL_TEXT" => "",
        "DETAIL_TEXT_TYPE" => "html",
    ]);
    echo "detail $id " . ($ok ? "cleared" : $element->LAST_ERROR) . "\n";
}

if (isset($GLOBALS["CACHE_MANAGER"])) {
    $GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_4");
}
CIBlock::clearIblockTagCache(4);
BXClearCache(true);
echo "done\n";
