INSERT INTO tt_content
    (uid, pid, CType, header, bodytext, colPos, sys_language_uid, l18n_parent, pi_flexform)
VALUES
    -- Homepage (pid 1/2)
    (1, 1, 'header', 'Welcome to our website', '', 0, 0, 0, ''),
    (2, 2, 'header', 'Willkommen auf unserer Webseite', '', 0, 1, 1, ''),
    (3, 1, 'text', 'Introduction', '<p>This is a demo website showcasing various content elements. Browse through our pages to learn more about our work.</p>', 0, 0, 0, ''),
    (4, 2, 'text', 'Einführung', '<p>Dies ist eine Demo-Website mit verschiedenen Inhaltselementen. Durchsuchen Sie unsere Seiten, um mehr über unsere Arbeit zu erfahren.</p>', 0, 1, 3, ''),
    (5, 1, 'textpic', 'Our Mission', '<p>We are committed to excellence in research and education.</p>', 0, 0, 0, ''),
    (6, 2, 'textpic', 'Unsere Mission', '<p>Wir sind der Exzellenz in Forschung und Bildung verpflichtet.</p>', 0, 1, 5, ''),

    -- About us page (pid 3/4)
    (7, 3, 'text', 'About Us', '<p>Learn more about our team and history. We have been working in this field for over 20 years.</p>', 0, 0, 0, ''),
    (8, 4, 'text', 'Über Uns', '<p>Erfahren Sie mehr über unser Team und unsere Geschichte. Wir arbeiten seit über 20 Jahren in diesem Bereich.</p>', 0, 1, 7, ''),
    (9, 3, 'text', 'Our Values', '<p>Integrity, innovation, and collaboration are at the core of everything we do.</p>', 0, 0, 0, ''),
    (10, 4, 'text', 'Unsere Werte', '<p>Integrität, Innovation und Zusammenarbeit stehen im Mittelpunkt unserer Arbeit.</p>', 0, 1, 9, ''),

    -- Research page (pid 5/6)
    (11, 5, 'text', 'Research Overview', '<p>Our research focuses on cutting-edge topics and innovative solutions.</p>', 0, 0, 0, ''),
    (12, 6, 'text', 'Forschungsübersicht', '<p>Unsere Forschung konzentriert sich auf zukunftsweisende Themen und innovative Lösungen.</p>', 0, 1, 11, ''),
    (13, 5, 'news_pi1', 'News', '', 0, 0, 0, ''),
    (14, 6, 'news_pi1', 'Nachrichten', '', 0, 1, 13, '');
