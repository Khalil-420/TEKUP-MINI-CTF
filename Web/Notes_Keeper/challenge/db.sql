-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `username` varchar(50) NOT NULL,
 `password` varchar(255) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES
(1,'adam','$2y$10$Up9yJPSBtUDfOST1F1pFg.x0sS17aJLTBvDvezMlmPnT.W848vUH2'),
(2,'Securinets','$2y$10$F895wneGquZbr4ToRymemuV6lupwngX8ndsVFXvkNGfWO.C7wyNJi');

-- Table structure for table `messages`
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `content` text NOT NULL,
 PRIMARY KEY (`id`),
 KEY `user_id` (`user_id`),
 CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Dumping data for table `messages`
INSERT INTO `messages` VALUES
(1,1,'Today was a terrible one for the army, they got many of us..'),
(2,1,'How could someone imagine some unarmed guys could set such traps and kill this many.. things are getting serious!'),
(3,1,'Note to Self: Don\'t forget to call Sam at 2PM'),
(4,1,'URGENT: WE NEED MORE BACKUP !!!!!'),
(5,1,'SAM if you\'re seeing this, tell my family that i love them this might be my last day! these Palestinians are stronger than we thought..'),
(6,1,'I almost forgot, here\'s the key to my house Sam : Securinets{N0T3S_L34KED} get in there and open the book I left on the table, check page number 32! '),
(7,1,'You\'re now dead, Adam!'),
(32,2,'R3JlYXQgam9iIGNoYW1waW9uLCBpbXByZXNzaXZlIGhvdyB5b3UgZ290IGhlcmUsIHlvdSBoYXZlIHNvbWUgc2tpbGxzIG91dCB0aGVyZSEgRG9uJ3QgZm9yZ2V0IHRvIHByYXkgZm9yIG91ciBicm90aGVycyBhbmQgc2lzdGVycywgVklWQSBQQUxFU1RJTkEh');


