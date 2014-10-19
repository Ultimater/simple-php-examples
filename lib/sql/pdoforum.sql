CREATE TABLE  `users` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `acl` tinyint(1) default 0,
    `uid` varchar(31),
    `fname` varchar(31),
    `lname` varchar(31),
    `email` varchar(63),
    `pwkey` varchar(8),
    `passwd` varchar(255),
    `updated` datetime,
    `created` datetime
);

CREATE TABLE categories (
  id                INT(8) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  cat_name              VARCHAR(255) NOT NULL,
  cat_description       VARCHAR(255) NOT NULL,
  UNIQUE INDEX cat_name_unique (cat_name)
);

CREATE TABLE topics (
  id              INT(8) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  topic_subject         VARCHAR(255) NOT NULL,
  topic_date            DATETIME NOT NULL,
  topic_cat             INT(8) NOT NULL,
  topic_by              INT(8) NOT NULL
);

CREATE TABLE posts (
  id               INT(8) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  post_content          TEXT NOT NULL,
  post_date             DATETIME NOT NULL,
  post_topic            INT(8) NOT NULL,
  post_by               INT(8) NOT NULL
);

ALTER TABLE topics ADD FOREIGN KEY(topic_cat) REFERENCES categories(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE topics ADD FOREIGN KEY(topic_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE posts ADD FOREIGN KEY(post_topic) REFERENCES topics(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE posts ADD FOREIGN KEY(post_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE;
