-- Impact Analysis Query hardcoded for 'Server 1'
WITH RECURSIVE related_impacts(name, class, id, parent_class, parent_id, redundancy, backtracking) as (
    /* Anchor query */
    SELECT DISTINCT IF((`SOURCE`.`finalclass` IN ('Middleware', 'DBServer', 'WebServer', 'PCSoftware', 'OtherSoftware')), CAST(CONCAT(COALESCE(`SOURCE`.`name`, ''), COALESCE(' ', ''), COALESCE(`FunctionalCI_system_id`.`name`, '')) AS CHAR), CAST(CONCAT(COALESCE(`SOURCE`.`name`, '')) AS CHAR)) AS `name`,
 `SOURCE`.`finalclass` AS `class`,
 `SOURCE`.`id` AS `id`,
 CAST(null AS CHAR(255)) AS `parent_class`,
 CAST(null AS UNSIGNED) AS `parent_id`,
 CAST(null AS CHAR(250)) AS `redundancy`,
 CAST(0 AS UNSIGNED) AS `backtracking`
 FROM
   `functionalci` AS `SOURCE`
   LEFT JOIN (
      `softwareinstance` AS `SOURCE_poly_SoftwareInstance`
      INNER JOIN
         `functionalci` AS `FunctionalCI_system_id`
       ON `SOURCE_poly_SoftwareInstance`.`functionalci_id` = `FunctionalCI_system_id`.`id`
   ) ON `SOURCE`.`id` = `SOURCE_poly_SoftwareInstance`.`id`
 WHERE (`SOURCE`.`name` = 'Server1')

    UNION DISTINCT
    /* Recursive part */
    (
        SELECT IF((`DOWN`.`finalclass` IN ('Team', 'Contact')), CAST(CONCAT(COALESCE(`DOWN`.`name`, '')) AS CHAR), CAST(CONCAT(COALESCE(`DOWN_poly_Person`.`first_name`, ''), COALESCE(' ', ''), COALESCE(`DOWN`.`name`, '')) AS CHAR)) AS `name`,
 `DOWN`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `functionalci` AS `UP`
   INNER JOIN (
      `contact` AS `DOWN`
      INNER JOIN
         `lnkcontacttofunctionalci` AS `lnk`
       ON `DOWN`.`id` = `lnk`.`contact_id`
      LEFT JOIN
         `person` AS `DOWN_poly_Person`
       ON `DOWN`.`id` = `DOWN_poly_Person`.`id`
   ) ON (`lnk`.`functionalci_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('PhysicalDevice', 'ConnectableCI', 'DatacenterDevice', 'NetworkDevice', 'Server', 'ApplicationSolution', 'BusinessProcess', 'SoftwareInstance', 'Middleware', 'DBServer', 'WebServer', 'PCSoftware', 'OtherSoftware', 'MiddlewareInstance', 'DatabaseSchema', 'WebApplication', 'Rack', 'Enclosure', 'PowerConnection', 'PowerSource', 'PDU', 'TelephonyCI', 'Phone', 'MobilePhone', 'IPPhone', 'Tablet', 'PC', 'Printer', 'Peripheral', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'VirtualDevice', 'VirtualHost', 'Hypervisor', 'Farm', 'VirtualMachine', 'FunctionalCI')
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 CAST(CONCAT(COALESCE('redundancy=', ''), COALESCE(`DOWN`.`redundancy`, '')) AS CHAR) AS `redundancy`,
 0 AS `backtracking`
 FROM
   `functionalci` AS `UP`
   INNER JOIN (
      `applicationsolution` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
      INNER JOIN
         `lnkapplicationsolutiontofunctionalci` AS `lnk`
       ON `DOWN`.`id` = `lnk`.`applicationsolution_id`
   ) ON (`lnk`.`functionalci_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('PhysicalDevice', 'ConnectableCI', 'DatacenterDevice', 'NetworkDevice', 'Server', 'ApplicationSolution', 'BusinessProcess', 'SoftwareInstance', 'Middleware', 'DBServer', 'WebServer', 'PCSoftware', 'OtherSoftware', 'MiddlewareInstance', 'DatabaseSchema', 'WebApplication', 'Rack', 'Enclosure', 'PowerConnection', 'PowerSource', 'PDU', 'TelephonyCI', 'Phone', 'MobilePhone', 'IPPhone', 'Tablet', 'PC', 'Printer', 'Peripheral', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'VirtualDevice', 'VirtualHost', 'Hypervisor', 'Farm', 'VirtualMachine', 'FunctionalCI')
 WHERE 1
   UNION SELECT IF((`DOWN`.`finalclass` IN ('Middleware', 'DBServer', 'WebServer', 'PCSoftware', 'OtherSoftware')), CAST(CONCAT(COALESCE(`DOWN`.`name`, ''), COALESCE(' ', ''), COALESCE(`FunctionalCI_system_id`.`name`, '')) AS CHAR), CAST(CONCAT(COALESCE(`DOWN`.`name`, '')) AS CHAR)) AS `name`,
 `DOWN`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 1 AS `backtracking`
 FROM
   `applicationsolution` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN`
      INNER JOIN
         `lnkapplicationsolutiontofunctionalci` AS `lnk`
       ON `DOWN`.`id` = `lnk`.`functionalci_id`
      LEFT JOIN (
         `softwareinstance` AS `DOWN_poly_SoftwareInstance`
         INNER JOIN
            `functionalci` AS `FunctionalCI_system_id`
          ON `DOWN_poly_SoftwareInstance`.`functionalci_id` = `FunctionalCI_system_id`.`id`
      ) ON `DOWN`.`id` = `DOWN_poly_SoftwareInstance`.`id`
   ) ON (`lnk`.`applicationsolution_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'ApplicationSolution'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, ''), COALESCE(' ', ''), COALESCE(`FunctionalCI_system_id`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `functionalci` AS `UP`
   INNER JOIN (
      `softwareinstance` AS `DOWN`
      INNER JOIN
         `functionalci` AS `FunctionalCI_system_id`
       ON `DOWN`.`functionalci_id` = `FunctionalCI_system_id`.`id`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`functionalci_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('PhysicalDevice', 'ConnectableCI', 'DatacenterDevice', 'NetworkDevice', 'Server', 'ApplicationSolution', 'BusinessProcess', 'SoftwareInstance', 'Middleware', 'DBServer', 'WebServer', 'PCSoftware', 'OtherSoftware', 'MiddlewareInstance', 'DatabaseSchema', 'WebApplication', 'Rack', 'Enclosure', 'PowerConnection', 'PowerSource', 'PDU', 'TelephonyCI', 'Phone', 'MobilePhone', 'IPPhone', 'Tablet', 'PC', 'Printer', 'Peripheral', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'VirtualDevice', 'VirtualHost', 'Hypervisor', 'Farm', 'VirtualMachine', 'FunctionalCI')
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `applicationsolution` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN_FunctionalCI`
      INNER JOIN
         `lnkapplicationsolutiontobusinessprocess` AS `lnk`
       ON `DOWN_FunctionalCI`.`id` = `lnk`.`businessprocess_id`
   ) ON ((`lnk`.`applicationsolution_id` = `UP`.id) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('BusinessProcess')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'ApplicationSolution'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `middleware` AS `UP`
   INNER JOIN (
      `middlewareinstance` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`middleware_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'Middleware'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `dbserver` AS `UP`
   INNER JOIN (
      `databaseschema` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`dbserver_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'DBServer'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `webserver` AS `UP`
   INNER JOIN (
      `webapplication` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`webserver_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'WebServer'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `networkdevice` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN_FunctionalCI`
      INNER JOIN
         `lnkconnectablecitonetworkdevice` AS `l1`
       ON `DOWN_FunctionalCI`.`id` = `l1`.`connectableci_id`
   ) ON (((`l1`.`networkdevice_id` = `UP`.id) AND (`l1`.`type` = 'downlink')) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('DatacenterDevice', 'NetworkDevice', 'Server', 'PC', 'Printer', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'ConnectableCI')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'NetworkDevice'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `server` AS `UP`
   INNER JOIN (
      `hypervisor` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`server_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'Server'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`StorageSystem_storagesystem_id_FunctionalCI`.`name`, ''), COALESCE(' ', ''), COALESCE(`DOWN`.`name`, '')) AS CHAR) AS `name`,
 'LogicalVolume' AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `storagesystem` AS `UP`
   INNER JOIN (
      `logicalvolume` AS `DOWN`
      INNER JOIN
         `functionalci` AS `StorageSystem_storagesystem_id_FunctionalCI`
       ON `DOWN`.`storagesystem_id` = `StorageSystem_storagesystem_id_FunctionalCI`.`id`
   ) ON ((`DOWN`.`storagesystem_id` = `UP`.id) AND COALESCE((`StorageSystem_storagesystem_id_FunctionalCI`.`finalclass` IN ('StorageSystem')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'StorageSystem'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `sanswitch` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN_FunctionalCI`
      INNER JOIN
         `lnkdatacenterdevicetosan` AS `lnk`
       ON `DOWN_FunctionalCI`.`id` = `lnk`.`datacenterdevice_id`
   ) ON ((`lnk`.`san_id` = `UP`.id) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('NetworkDevice', 'Server', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'DatacenterDevice')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'SANSwitch'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN`.`name`, '')) AS CHAR) AS `name`,
 'Tape' AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `tapelibrary` AS `UP`
   INNER JOIN
      `tape` AS `DOWN`
    ON (`DOWN`.`tapelibrary_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'TapeLibrary'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN`.`name`, '')) AS CHAR) AS `name`,
 'NASFileSystem' AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `nas` AS `UP`
   INNER JOIN
      `nasfilesystem` AS `DOWN`
    ON (`DOWN`.`nas_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'NAS'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 CAST(CONCAT(COALESCE('redundancy=', ''), COALESCE(`DOWN`.`redundancy`, '')) AS CHAR) AS `redundancy`,
 0 AS `backtracking`
 FROM
   `hypervisor` AS `UP`
   INNER JOIN (
      `farm` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`id` = `UP`.farm_id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'Hypervisor'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 1 AS `backtracking`
 FROM
   `farm` AS `UP`
   INNER JOIN (
      `hypervisor` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`farm_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'Farm'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `enclosure` AS `UP`
   INNER JOIN (
      `datacenterdevice` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`enclosure_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'Enclosure'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 CAST(CONCAT(COALESCE('redundancy=', ''), COALESCE(`DOWN`.`redundancy`, '')) AS CHAR) AS `redundancy`,
 0 AS `backtracking`
 FROM
   `powerconnection` AS `UP`
   INNER JOIN (
      `datacenterdevice` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON ((`DOWN`.`powera_id` = `UP`.id) OR (`DOWN`.`powerB_id` = `UP`.id))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('PowerSource', 'PDU', 'PowerConnection')
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 1 AS `backtracking`
 FROM
   `datacenterdevice` AS `UP`
   INNER JOIN
      `functionalci` AS `DOWN_FunctionalCI`
    ON (((`DOWN_FunctionalCI`.`id` = `UP`.powerA_id) OR (`DOWN_FunctionalCI`.`id` = `UP`.powerB_id)) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('PowerSource', 'PDU', 'PowerConnection')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('NetworkDevice', 'Server', 'StorageSystem', 'SANSwitch', 'TapeLibrary', 'NAS', 'DatacenterDevice')
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `powerconnection` AS `UP`
   INNER JOIN (
      `pdu` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`powerstart_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('PowerSource', 'PDU', 'PowerConnection')
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `logicalvolume` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN_FunctionalCI`
      INNER JOIN
         `lnkservertovolume` AS `lnk`
       ON `DOWN_FunctionalCI`.`id` = `lnk`.`server_id`
   ) ON ((`lnk`.`volume_id` = `UP`.id) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('Server')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'LogicalVolume'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN_FunctionalCI`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `logicalvolume` AS `UP`
   INNER JOIN (
      `functionalci` AS `DOWN_FunctionalCI`
      INNER JOIN
         `lnkvirtualdevicetovolume` AS `lnk`
       ON `DOWN_FunctionalCI`.`id` = `lnk`.`virtualdevice_id`
   ) ON ((`lnk`.`volume_id` = `UP`.id) AND COALESCE((`DOWN_FunctionalCI`.`finalclass` IN ('VirtualHost', 'Hypervisor', 'Farm', 'VirtualMachine', 'VirtualDevice')), 1))
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class = 'LogicalVolume'
 WHERE 1
   UNION SELECT CAST(CONCAT(COALESCE(`DOWN_FunctionalCI`.`name`, '')) AS CHAR) AS `name`,
 `DOWN_FunctionalCI`.`finalclass` AS `class`,
 `DOWN`.`id` AS `id`,
 `FOUND`.`class` AS `parent_class`,
 `FOUND`.`id` AS `parent_id`,
 NULL AS `redundancy`,
 0 AS `backtracking`
 FROM
   `virtualhost` AS `UP`
   INNER JOIN (
      `virtualmachine` AS `DOWN`
      INNER JOIN
         `functionalci` AS `DOWN_FunctionalCI`
       ON `DOWN`.`id` = `DOWN_FunctionalCI`.`id`
   ) ON (`DOWN`.`virtualhost_id` = `UP`.id)
   INNER JOIN
      `related_impacts` AS `FOUND`
    ON `UP`.id = `FOUND`.id AND `FOUND`.backtracking = 0 AND `FOUND`.class IN ('Hypervisor', 'Farm', 'VirtualHost')
 WHERE 1

    )
)
SELECT name, class, id, parent_class, parent_id, redundancy, backtracking FROM related_impacts
