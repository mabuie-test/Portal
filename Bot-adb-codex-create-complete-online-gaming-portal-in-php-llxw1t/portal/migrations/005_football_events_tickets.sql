-- Eventos de futebol, odds e bilhetes múltiplos (idempotente)
CREATE TABLE IF NOT EXISTS football_events (
  event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_code VARCHAR(64) NOT NULL UNIQUE,
  league VARCHAR(120) NOT NULL,
  home_team VARCHAR(120) NOT NULL,
  away_team VARCHAR(120) NOT NULL,
  starts_at DATETIME NOT NULL,
  status ENUM('scheduled','live','finished','postponed','cancelled') NOT NULL DEFAULT 'scheduled',
  home_score TINYINT UNSIGNED NULL,
  away_score TINYINT UNSIGNED NULL,
  first_scorer VARCHAR(120) NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_football_events_status_start (status, starts_at),
  CONSTRAINT fk_football_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_football_events_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS football_odds (
  odd_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  market_type VARCHAR(40) NOT NULL,
  selection_key VARCHAR(120) NOT NULL,
  line_value DECIMAL(8,2) NULL,
  odd DECIMAL(8,2) NOT NULL,
  status ENUM('open','suspended','settled','void') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_football_odds_market (event_id, market_type, selection_key, line_value),
  INDEX idx_football_odds_event (event_id, market_type, status),
  CONSTRAINT fk_football_odds_event FOREIGN KEY (event_id) REFERENCES football_events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS football_tickets (
  ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  ticket_code VARCHAR(32) NOT NULL UNIQUE,
  bet_type ENUM('single','multi') NOT NULL DEFAULT 'multi',
  stake DECIMAL(18,2) NOT NULL,
  total_odd DECIMAL(10,2) NOT NULL,
  bonus_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
  potential_payout DECIMAL(18,2) NOT NULL,
  final_payout DECIMAL(18,2) NULL,
  status ENUM('open','won','lost','void','partial_void','cashed_out') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  settled_at DATETIME NULL,
  INDEX idx_football_tickets_user (user_id, created_at),
  INDEX idx_football_tickets_status (status, created_at),
  CONSTRAINT fk_football_tickets_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS football_ticket_selections (
  selection_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  event_id BIGINT UNSIGNED NOT NULL,
  market_type VARCHAR(40) NOT NULL,
  selection_key VARCHAR(120) NOT NULL,
  line_value DECIMAL(8,2) NULL,
  odd DECIMAL(8,2) NOT NULL,
  status ENUM('open','won','lost','void') NOT NULL DEFAULT 'open',
  result_note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  settled_at DATETIME NULL,
  INDEX idx_football_sel_ticket (ticket_id, status),
  INDEX idx_football_sel_event (event_id, status),
  CONSTRAINT fk_football_sel_ticket FOREIGN KEY (ticket_id) REFERENCES football_tickets(ticket_id) ON DELETE CASCADE,
  CONSTRAINT fk_football_sel_event FOREIGN KEY (event_id) REFERENCES football_events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB;
