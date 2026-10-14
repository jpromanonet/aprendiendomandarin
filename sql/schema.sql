CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NULL,
  display_name VARCHAR(120) NOT NULL,
  avatar_url VARCHAR(500) NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_identities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  provider VARCHAR(32) NOT NULL,
  provider_user_id VARCHAR(190) NOT NULL,
  UNIQUE KEY uniq_provider (provider, provider_user_id),
  KEY idx_identity_user (user_id),
  CONSTRAINT fk_identity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS levels (
  id TINYINT UNSIGNED PRIMARY KEY,
  slug VARCHAR(32) NOT NULL,
  title VARCHAR(80) NOT NULL,
  stage VARCHAR(32) NOT NULL,
  stage_label VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  words_new INT NOT NULL,
  words_cumulative INT NOT NULL,
  sort_order TINYINT NOT NULL,
  UNIQUE KEY uniq_level_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level_id TINYINT UNSIGNED NOT NULL,
  slug VARCHAR(64) NOT NULL,
  number INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  summary TEXT NOT NULL,
  content_json LONGTEXT NOT NULL,
  UNIQUE KEY uniq_lesson (level_id, slug),
  KEY idx_lesson_level (level_id, number),
  CONSTRAINT fk_lesson_level FOREIGN KEY (level_id) REFERENCES levels (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quizzes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  pass_score TINYINT NOT NULL DEFAULT 70,
  max_attempts TINYINT NOT NULL DEFAULT 5,
  questions_json LONGTEXT NOT NULL,
  UNIQUE KEY uniq_quiz_lesson (lesson_id),
  CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dictionary_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hanzi VARCHAR(16) NOT NULL,
  pinyin VARCHAR(64) NOT NULL,
  pinyin_plain VARCHAR(64) NOT NULL,
  espanol VARCHAR(500) NOT NULL,
  espanol_plain VARCHAR(500) NOT NULL,
  hsk_level TINYINT UNSIGNED NOT NULL,
  example_zh VARCHAR(255) NOT NULL DEFAULT '',
  example_pinyin VARCHAR(255) NOT NULL DEFAULT '',
  example_es VARCHAR(500) NOT NULL DEFAULT '',
  KEY idx_dict_level (hsk_level),
  KEY idx_dict_py (pinyin_plain),
  KEY idx_dict_es (espanol_plain(80)),
  KEY idx_dict_hanzi (hanzi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  percent TINYINT NOT NULL DEFAULT 0,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  UNIQUE KEY uniq_progress (user_id, lesson_id),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS self_assessments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  attempt INT NOT NULL,
  score TINYINT NOT NULL,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  answers_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_attempt (user_id, lesson_id, attempt),
  KEY idx_assess_user (user_id),
  CONSTRAINT fk_assess_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_assess_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  size_label VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  level_id TINYINT UNSIGNED NOT NULL,
  code VARCHAR(40) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cert (user_id, level_id),
  UNIQUE KEY uniq_cert_code (code),
  CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_level FOREIGN KEY (level_id) REFERENCES levels (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code VARCHAR(40) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_course_cert_user (user_id),
  UNIQUE KEY uniq_course_cert_code (code),
  CONSTRAINT fk_course_cert_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
