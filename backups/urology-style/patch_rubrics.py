#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Style the Patients rubric and the staff list filters like the doctor cards."""
import shutil
from pathlib import Path

ROOT = Path("/home/host1510/public_html/bitrix/templates/aspro-medc2")
BACKUP = Path("/home/host1510/backups/rubric-style-2026-10-21")
DETAIL = ROOT / "components/bitrix/news.detail/staff/template.php"
LIST = ROOT / "components/bitrix/news.list/staff-elements-1/template.php"
SCRIPT = ROOT / "components/bitrix/news.list/staff-elements-1/script.js"
CUSTOM = ROOT / "css/custom.css"

MARKER = "/* staff-rubric-cards */"

CSS = r"""
/* staff-rubric-cards */
.property.adult_child .title-prop {
	color: #008080 !important;
	font-family: Arial, sans-serif;
	font-size: 20px;
	font-weight: 600;
	margin: 18px 0 8px;
}
.property.adult_child li:before {
	content: "\2013" !important;
}
.staff-filter-wrapp .filtr-wrap:not(:has(.name)) {
	display: none;
}
.staff-filter-wrapp .filtr-wrap {
	border: none;
	background: #f9f9f9;
	border-radius: 12px;
	margin-bottom: 16px;
	padding: 4px 8px 8px;
}
.staff-filter-wrapp .filtr-wrap .name {
	color: #008080;
	font-family: Arial, sans-serif;
	font-size: 20px;
	font-weight: 600;
	padding: 14px 40px 12px 16px;
}
.staff-filter-wrapp .filtr-wrap .filtr {
	position: static;
	left: auto;
	top: auto;
	width: auto;
	max-height: none;
	border: none;
	background: transparent;
	padding: 0 4px 4px;
	overflow: visible;
}
.staff-filter-wrapp .filtr-wrap .filtr .filtr-option {
	display: block;
	width: 100%;
	box-sizing: border-box;
	text-align: left;
	background: #fff;
	border: none;
	border-radius: 12px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
	margin: 0 0 8px;
	padding: 14px 18px 14px 36px;
	font-family: Arial, sans-serif;
	font-size: 16px;
	line-height: 1.5;
	color: #333;
	position: relative;
	cursor: pointer;
}
.staff-filter-wrapp .filtr-wrap .filtr .filtr-option:before {
	content: "\2013";
	position: absolute;
	left: 16px;
	top: 14px;
	color: #008080;
}
.staff-filter-wrapp .filtr-wrap .filtr .filtr-option:hover,
.staff-filter-wrapp .filtr-wrap .filtr .filtr-option.is-active {
	background: #e6f7f7;
}
"""

ADULT_HTML = """<?elseif($PCODE == "ADULT_CHILD"):?>
															<div class="value">
																<section style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 16px 20px; color: #333; margin: 8px 0 18px;">
																	<div style="max-width: 900px; margin: auto; background: #ffffff; padding: 24px 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
																		<ul style="font-size: 16px; line-height: 1.8; padding-left: 20px; margin: 0;">
																			<?foreach(array_filter(array_map('trim', explode(',', $val))) as $adultPart):?>
																				<li><?=htmlspecialcharsbx($adultPart)?></li>
																			<?endforeach;?>
																		</ul>
																	</div>
																</section>
															</div>
														<?else:?>"""


def backup(path: Path):
    BACKUP.mkdir(parents=True, exist_ok=True)
    dest = BACKUP / path.name
    if path.name == "template.php":
        dest = BACKUP / (path.parent.name + "-template.php")
    if not dest.exists():
        shutil.copy2(path, dest)
        print("backup", dest)


def replace_once(path: Path, old: str, new: str, label: str):
    text = path.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{label}: expected 1 occurrence, found {count}")
    path.write_text(text.replace(old, new, 1), encoding="utf-8")
    print("patched", label)


def main():
    for path in (DETAIL, LIST, SCRIPT, CUSTOM):
        backup(path)

    detail = DETAIL.read_text(encoding="utf-8")
    needle = '<?elseif($PCODE == "EMAIL"):?>'
    idx = detail.find(needle)
    if idx < 0:
        raise SystemExit("EMAIL branch not found")
    else_at = detail.find("<?else:?>", idx)
    endif_at = detail.find("<?endif;?>", else_at)
    if else_at < 0 or endif_at < 0 or else_at - idx > 800:
        raise SystemExit("else branch not near EMAIL")
    old_else = detail[else_at:endif_at]
    if "ADULT_CHILD" in detail:
        print("detail already has ADULT_CHILD")
    else:
        detail = detail[:else_at] + ADULT_HTML + "\n" + detail[endif_at:]
        DETAIL.write_text(detail, encoding="utf-8")
        print("patched detail, old else was:", old_else.strip()[:120])

    replace_once(
        LIST,
        '<input data-id="" data-type="patient" value="Все">',
        '<button type="button" class="filtr-option<?=($arParams[\'FILTER\'][\'PROPERTY_ADULT_CHILD_VALUE\']?\'\':\' is-active\')?>" data-id="" data-type="patient">Все</button>',
        "patient all",
    )
    replace_once(
        LIST,
        '<input data-id="<?=$v?>" data-type="patient" value="<?=$v?>">',
        '<button type="button" class="filtr-option<?=($arParams[\'FILTER\'][\'PROPERTY_ADULT_CHILD_VALUE\']==$v?\' is-active\':\'\')?>" data-id="<?=$v?>" data-type="patient"><?=$v?></button>',
        "patient item",
    )
    replace_once(
        LIST,
        '<input data-id="" data-type="filial" value="Все">',
        '<button type="button" class="filtr-option<?=($arParams[\'FILTER\'][\'PROPERTY_LINK_ADDRESS\']?\'\':\' is-active\')?>" data-id="" data-type="filial">Все</button>',
        "filial all",
    )
    replace_once(
        LIST,
        '<input data-id="<?=$k?>" data-type="filial" value="<?=$v?>">',
        '<button type="button" class="filtr-option<?=((string)$arParams[\'FILTER\'][\'PROPERTY_LINK_ADDRESS\']===(string)$k?\' is-active\':\'\')?>" data-id="<?=$k?>" data-type="filial"><?=$v?></button>',
        "filial item",
    )
    replace_once(
        LIST,
        '<input data-id="<?=$arParams[\'SEF_FOLDER\']?>" data-type="section" value="Все">',
        '<button type="button" class="filtr-option" data-id="<?=$arParams[\'SEF_FOLDER\']?>" data-type="section">Все</button>',
        "section all",
    )
    replace_once(
        LIST,
        '<input data-id="<?=$v[\'SECTION_PAGE_URL\']?>" data-code="<?=$v[\'CODE\']?>" data-type="section" value="<?=$v[\'NAME\']?>">',
        '<button type="button" class="filtr-option" data-id="<?=$v[\'SECTION_PAGE_URL\']?>" data-code="<?=$v[\'CODE\']?>" data-type="section"><?=$v[\'NAME\']?></button>',
        "section item",
    )

    script = SCRIPT.read_text(encoding="utf-8")
    old_js = "$('.filtr-wrap .filtr input').on('click', function(){"
    new_js = "$('.filtr-wrap .filtr .filtr-option').on('click', function(e){\n            e.preventDefault();\n            e.stopPropagation();"
    if old_js not in script:
        if "filtr-option" in script:
            print("script already patched")
        else:
            raise SystemExit("script selector not found")
    else:
        SCRIPT.write_text(script.replace(old_js, new_js, 1), encoding="utf-8")
        print("patched script")

    css = CUSTOM.read_text(encoding="utf-8")
    if MARKER not in css:
        if not css.endswith("\n"):
            css += "\n"
        CUSTOM.write_text(css + CSS, encoding="utf-8")
        print("appended css")
    else:
        print("css already present")

    print("done")


if __name__ == "__main__":
    main()
