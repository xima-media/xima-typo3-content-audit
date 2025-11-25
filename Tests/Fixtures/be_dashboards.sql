-- Dashboard for Content Audit widgets
INSERT INTO `be_dashboards` (`identifier`, `title`, `widgets`, `crdate`, `tstamp`, `cruser_id`)
VALUES
    ('content-audit-demo-admin',
    'Content Audit',
    '{
        "contentAuditStalePages": {"identifier": "contentAuditStalePages"},
        "contentAuditFreshPages": {"identifier": "contentAuditFreshPages"},
        "contentAuditHiddenPages": {"identifier": "contentAuditHiddenPages"},
        "contentAuditHiddenContent": {"identifier": "contentAuditHiddenContent"},
        "contentAuditMissingImageFields": {"identifier": "contentAuditMissingImageFields"}
    }',
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    1),
    ('content-audit-demo-editor',
    'Content Audit',
    '{
        "contentAuditStalePages": {"identifier": "contentAuditStalePages"},
        "contentAuditFreshPages": {"identifier": "contentAuditFreshPages"},
        "contentAuditHiddenPages": {"identifier": "contentAuditHiddenPages"},
        "contentAuditHiddenContent": {"identifier": "contentAuditHiddenContent"},
        "contentAuditMissingImageFields": {"identifier": "contentAuditMissingImageFields"}
    }',
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP(),
    3);
