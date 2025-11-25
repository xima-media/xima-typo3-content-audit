INSERT INTO `sys_template` (`pid`, `title`, `root`, `clear`, `include_static_file`, `config`, `tstamp`, `crdate`)
VALUES (1, 'MAIN TEMPLATE', 1, 3, 'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/', 'page = PAGE
page.10 = CONTENT
page.10 {
    table = tt_content
    select {
        orderBy = sorting
        where = {#colPos}=0
    }
}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
