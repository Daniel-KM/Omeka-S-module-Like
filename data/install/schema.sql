CREATE TABLE `like` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `owner_id` INT NOT NULL,
    `resource_id` INT NOT NULL,
    `liked` TINYINT(1) NOT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME DEFAULT NULL,
    INDEX `IDX_AC6340B37E3C61F9` (`owner_id`),
    INDEX `IDX_AC6340B389329D25` (`resource_id`),
    INDEX `IDX_AC6340B3CA19CBBA89329D25` (`liked`, `resource_id`),
    UNIQUE INDEX `UNIQ_AC6340B37E3C61F989329D25` (`owner_id`, `resource_id`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci` ENGINE = `InnoDB`;

ALTER TABLE `like` ADD CONSTRAINT `FK_AC6340B37E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
ALTER TABLE `like` ADD CONSTRAINT `FK_AC6340B389329D25` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE;

-- View for like counts per resource, avoiding complex queries.
CREATE OR REPLACE VIEW `like_count` AS
SELECT
    `resource_id`,
    SUM(CASE WHEN `liked` = 1 THEN 1 ELSE 0 END) AS `likes`,
    SUM(CASE WHEN `liked` = 0 THEN 1 ELSE 0 END) AS `dislikes`,
    COUNT(*) AS `total`
FROM `like`
GROUP BY `resource_id`;
