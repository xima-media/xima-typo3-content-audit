-- Test data for Content Audit widgets

-- Add stale pages
INSERT INTO `pages`
    (`uid`, `pid`, `title`, `slug`, `sys_language_uid`, `l10n_parent`, `l10n_source`, `perms_userid`,
    `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`, `doktype`, `is_siteroot`, `module`, `tstamp`)
VALUES
    (100, 1, 'Stale page 1', '/stale-page-1', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 190 DAY))),
    (101, 1, 'Not so stale page 2', '/not-so-stale-page-2', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 100 DAY))),
    (102, 1, 'Very stale page 3', '/very-stale-page-3', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 400 DAY)));

-- Add fresh pages
INSERT INTO `pages`
    (`uid`, `pid`, `title`, `slug`, `sys_language_uid`, `l10n_parent`, `l10n_source`, `perms_userid`,
    `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`, `doktype`, `is_siteroot`, `module`, `tstamp`)
VALUES
    (103, 1, 'Fresh page 1', '/fresh-page-1', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP()),
    (104, 1, 'Not so fresh page 2', '/not-so-fresh-page-2', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 100 DAY))),
    (105, 1, 'Very fresh page 3', '/very-fresh-page-3', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)));

-- Add long hidden pages
INSERT INTO `pages`
    (`uid`, `pid`, `title`, `slug`, `sys_language_uid`, `l10n_parent`, `l10n_source`, `perms_userid`,
    `perms_groupid`, `perms_user`, `perms_group`, `perms_everybody`, `doktype`, `is_siteroot`, `module`, `hidden`, `tstamp`)
VALUES
    (106, 1, 'Long hidden page 1', '/long-hidden-page-1', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 800 DAY))),
    (107, 1, 'Not so long hidden page 2', '/not-so-long-hidden-page-2', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 200 DAY))),
    (108, 1, 'Very long hidden page 3', '/very-long-hidden-page-3', 0, 0, 0, 1, 1, 31, 31, 1, 1, 0, '', 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 500 DAY)));

-- Add long hidden content elements
INSERT INTO tt_content
    (uid, pid, CType, header, bodytext, colPos, sys_language_uid, l18n_parent, hidden, tstamp)
VALUES
    (100, 1, 'text', 'Long hidden content 1', '<p>This content has been hidden for a long time.</p>', 0, 0, 0, 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 800 DAY))),
    (101, 3, 'text', 'Not so long hidden content 2', '<p>This content is hidden but not for so long.</p>', 0, 0, 0, 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 450 DAY))),
    (102, 3, 'text', 'Very long hidden content 3', '<p>This content is also hidden for a very long time.</p>', 0, 0, 0, 1, UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 450 DAY)));

-- Add sys_file entries for testing missing image fields
INSERT INTO `sys_file`
    (`uid`, `pid`, `tstamp`, `last_indexed`, `missing`, `storage`, `type`, `metadata`, `identifier`, `identifier_hash`, `extension`, `mime_type`, `name`, `sha1`, `size`, `creation_date`, `modification_date`)
VALUES
    (100, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 2, 0, '/test-images/image1.jpg', SHA1('/test-images/image1.jpg'), 'jpg', 'image/jpeg', 'image1.jpg', SHA1('dummy'), 12345, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (101, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 2, 0, '/test-images/image2.jpg', SHA1('/test-images/image2.jpg'), 'jpg', 'image/jpeg', 'image2.jpg', SHA1('dummy'), 12345, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `sys_file_metadata`
    (`uid`, `pid`, `tstamp`, `crdate`, `file`, `title`, `description`, `alternative`)
VALUES
    (100, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'Test Image 1', 'This image is missing alt text', ''),
    (101, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 101, 'Test Image 2 with Alt', 'This image has alt text', 'Proper alt text here');

INSERT INTO `sys_file_reference`
    (`uid`, `pid`, `tstamp`, `crdate`, `uid_local`, `uid_foreign`, `tablenames`, `fieldname`)
VALUES
    (100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 5, 'tt_content', 'assets'),
    (101, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 101, 7, 'tt_content', 'assets');
