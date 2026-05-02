/*
 Distributed appointments test data

 Original file: C:/Users/yasn/Documents/appointments.sql
 Purpose: spread appointments across separate days and times for cleaner timeline testing.
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for appointments
-- ----------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` bigint UNSIGNED NOT NULL,
  `member_id` bigint UNSIGNED NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `blocks` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booked',
  `notes_internal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `cancelled_at` datetime NULL DEFAULT NULL,
  `cancel_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cancelled_by_user_id` bigint UNSIGNED NULL DEFAULT NULL,
  `created_by_user_id` bigint UNSIGNED NULL DEFAULT NULL,
  `parent_id` bigint UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `appointments_cancelled_by_user_id_foreign`(`cancelled_by_user_id` ASC) USING BTREE,
  INDEX `appointments_created_by_user_id_foreign`(`created_by_user_id` ASC) USING BTREE,
  INDEX `appointments_parent_id_foreign`(`parent_id` ASC) USING BTREE,
  INDEX `appointments_provider_id_start_at_index`(`provider_id` ASC, `start_at` ASC) USING BTREE,
  INDEX `appointments_member_id_start_at_index`(`member_id` ASC, `start_at` ASC) USING BTREE,
  INDEX `appointments_status_start_at_index`(`status` ASC, `start_at` ASC) USING BTREE,
  CONSTRAINT `appointments_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `appointments_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `appointments_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `appointments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `appointments_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 52 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of appointments
-- ----------------------------
INSERT INTO `appointments` VALUES (25, 1, 1, '2026-04-06 09:00:00', '2026-04-06 09:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, NULL, '2026-04-04 17:14:01', '2026-04-05 21:38:56');
INSERT INTO `appointments` VALUES (27, 1, 4, '2026-04-08 09:00:00', '2026-04-08 09:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-05 00:57:54', '2026-04-05 21:30:37');
INSERT INTO `appointments` VALUES (28, 1, 4, '2026-04-09 10:00:00', '2026-04-09 10:30:00', 1, 'completed', NULL, NULL, NULL, NULL, NULL, 27, '2026-04-05 21:30:37', '2026-04-05 21:30:37');
INSERT INTO `appointments` VALUES (29, 1, 1, '2026-04-07 10:30:00', '2026-04-07 11:00:00', 1, 'completed', NULL, NULL, NULL, NULL, 1, 25, '2026-04-05 21:38:56', '2026-04-05 21:38:56');
INSERT INTO `appointments` VALUES (30, 1, 4, '2026-04-10 11:00:00', '2026-04-10 11:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-14 00:45:13', '2026-04-14 12:46:54');
INSERT INTO `appointments` VALUES (31, 1, 4, '2026-04-13 09:30:00', '2026-04-13 10:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 30, '2026-04-14 12:46:54', '2026-04-14 19:39:21');
INSERT INTO `appointments` VALUES (32, 2, 4, '2026-04-14 10:30:00', '2026-04-14 11:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 31, '2026-04-14 19:39:21', '2026-04-14 19:41:32');
INSERT INTO `appointments` VALUES (33, 2, 4, '2026-04-15 13:30:00', '2026-04-15 14:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 32, '2026-04-14 19:41:32', '2026-04-14 20:10:58');
INSERT INTO `appointments` VALUES (34, 2, 4, '2026-04-16 14:30:00', '2026-04-16 15:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 33, '2026-04-14 20:10:58', '2026-04-14 20:11:08');
INSERT INTO `appointments` VALUES (35, 1, 4, '2026-04-17 15:30:00', '2026-04-17 16:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 34, '2026-04-14 20:11:08', '2026-04-14 20:21:00');
INSERT INTO `appointments` VALUES (36, 1, 4, '2026-04-20 09:00:00', '2026-04-20 09:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 35, '2026-04-14 20:21:00', '2026-04-14 20:21:14');
INSERT INTO `appointments` VALUES (37, 1, 4, '2026-04-21 10:00:00', '2026-04-21 10:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 36, '2026-04-14 20:21:14', '2026-04-14 20:21:23');
INSERT INTO `appointments` VALUES (38, 1, 4, '2026-04-22 11:00:00', '2026-04-22 11:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 37, '2026-04-14 20:21:23', '2026-04-14 20:21:38');
INSERT INTO `appointments` VALUES (39, 1, 4, '2026-04-23 12:00:00', '2026-04-23 12:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 38, '2026-04-14 20:21:38', '2026-04-14 20:21:44');
INSERT INTO `appointments` VALUES (40, 1, 4, '2026-04-24 13:00:00', '2026-04-24 13:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 39, '2026-04-14 20:21:44', '2026-04-14 20:35:02');
INSERT INTO `appointments` VALUES (41, 1, 4, '2026-04-27 14:00:00', '2026-04-27 14:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 40, '2026-04-14 20:35:02', '2026-04-14 20:35:05');
INSERT INTO `appointments` VALUES (42, 1, 4, '2026-04-28 15:00:00', '2026-04-28 15:30:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 41, '2026-04-14 20:35:05', '2026-04-14 20:35:41');
INSERT INTO `appointments` VALUES (43, 1, 4, '2026-04-29 09:30:00', '2026-04-29 10:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 42, '2026-04-14 20:35:41', '2026-04-14 20:36:34');
INSERT INTO `appointments` VALUES (44, 1, 4, '2026-04-30 10:30:00', '2026-04-30 11:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 43, '2026-04-14 20:36:34', '2026-04-14 20:39:24');
INSERT INTO `appointments` VALUES (45, 1, 4, '2026-05-01 11:30:00', '2026-05-01 12:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 44, '2026-04-14 20:39:24', '2026-04-14 21:22:12');
INSERT INTO `appointments` VALUES (46, 1, 4, '2026-05-04 12:30:00', '2026-05-04 13:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 45, '2026-04-14 21:22:12', '2026-04-14 21:22:27');
INSERT INTO `appointments` VALUES (47, 1, 4, '2026-05-05 13:30:00', '2026-05-05 14:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 46, '2026-04-14 21:22:27', '2026-04-14 21:37:29');
INSERT INTO `appointments` VALUES (48, 2, 4, '2026-05-06 14:30:00', '2026-05-06 15:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 47, '2026-04-14 21:37:29', '2026-04-14 21:44:05');
INSERT INTO `appointments` VALUES (49, 1, 4, '2026-05-07 15:30:00', '2026-05-07 16:00:00', 1, 'transferred', NULL, NULL, NULL, NULL, 1, 48, '2026-04-14 21:44:05', '2026-04-14 22:40:39');
INSERT INTO `appointments` VALUES (50, 1, 4, '2026-05-08 09:00:00', '2026-05-08 10:00:00', 2, 'transferred', NULL, NULL, NULL, NULL, NULL, 49, '2026-04-14 22:40:39', '2026-04-14 23:21:28');
INSERT INTO `appointments` VALUES (51, 1, 4, '2026-05-11 10:30:00', '2026-05-11 11:30:00', 2, 'booked', NULL, NULL, NULL, NULL, 1, 50, '2026-04-14 23:21:28', '2026-04-14 23:21:28');

SET FOREIGN_KEY_CHECKS = 1;
