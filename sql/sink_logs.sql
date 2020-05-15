-- ----------------------------
-- Table structure for sink_logs
-- ----------------------------
DROP TABLE IF EXISTS `sink_logs`;
CREATE TABLE `sink_logs` (
  `id` int(28) NOT NULL AUTO_INCREMENT,
  `result` varchar(100) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `sanitized` int(1) DEFAULT NULL,
  `input` text,
  `seconds_taken` decimal(15,6) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

