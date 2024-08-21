CREATE OR REPLACE VIEW `%( DB_NAME )%`.`view::document::metadata` AS

SELECT
    document_table.`id` AS `id`,
    document_table.`path` AS `path`,
    document_table.`title` AS `title`,
    document_table.`description` AS `description`,
    document_table.`datetime.insert` AS `datetime.insert`,
    document_table.`datetime.update` AS `datetime.update`,
    document_table.`datetime.option.active` AS `datetime.option.active`,
    document_table.`datetime.option.sitemap` AS `datetime.option.sitemap`,

    GROUP_CONCAT( DISTINCT IFNULL( CONCAT( tag_table.`value`, '=', tag_table.`color` ), '' ) ORDER BY tag_table.`datetime.insert` DESC SEPARATOR ';' ) AS `tag_list`,

    user_table.`id` AS `owner.id`,
    user_table.`username` AS `owner.username`,
    user_table.`profile.name` AS `owner.name`,
    user_table.`profile.surname` AS `owner.surname`,
    user_table.`email` AS `owner.email`
FROM
    `%( DB_NAME )%`.`document` document_table
        INNER JOIN
    `%( DB_NAME )%`.`user` user_table
        ON
    (
        document_table.`owner` = user_table.`id`
    )
        LEFT OUTER JOIN
    `%( DB_NAME )%`.`document.tag` document_tag_table
        ON
    (
        document_table.`id` = document_tag_table.`document`
    )
        LEFT OUTER JOIN
    `%( DB_NAME )%`.`tag` tag_table
        ON
    (
        document_tag_table.`tag` = tag_table.`id`
    )
GROUP BY
    document_table.`id`
ORDER BY
    document_table.`id` DESC
;