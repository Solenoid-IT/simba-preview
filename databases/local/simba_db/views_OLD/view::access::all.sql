CREATE OR REPLACE VIEW `%( DB_NAME )%`.`view::access::all` AS

SELECT
    access_table.`login_method` AS `login_method`,
    access_table.`ip.address` AS `ip.address`,
    access_table.`ip.country.code` AS `ip.country.code`,
    access_table.`ip.country.name` AS `ip.country.name`,
    access_table.`ip.isp` AS `ip.isp`,
    access_table.`user_agent` AS `user_agent`,
    access_table.`browser` AS `browser`,
    access_table.`os` AS `os`,
    access_table.`hw` AS `hw`,
    access_table.`session` AS `session`,
    access_table.`datetime.insert` AS `datetime.insert`,

    user_table.`id` AS `user.id`,
    user_table.`username` AS `user.username`,
    user_table.`profile.name` AS `user.name`,
    user_table.`profile.surname` AS `user.surname`
FROM
    `%( DB_NAME )%`.`access` access_table
        INNER JOIN
    `%( DB_NAME )%`.`user` user_table
        ON
    (
        access_table.`user` = user_table.`id`
    )
ORDER BY
    access_table.`datetime.insert` DESC
;