-- Apartman/Site Yönetimi - Meeting Voting System
-- Migration 013: Enhanced Meeting Voting & Topics

PRAGMA foreign_keys=ON;

-- Toplantı konuları/oy birimleri (agenda items extended)
CREATE TABLE IF NOT EXISTS meeting_topics (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  meeting_id INTEGER NOT NULL,
  topic_title TEXT NOT NULL,
  topic_description TEXT,
  topic_type TEXT NOT NULL CHECK(topic_type IN ('information', 'voting', 'discussion', 'approval')),
  voting_enabled INTEGER DEFAULT 0, -- Is voting allowed for this topic?
  voting_type TEXT CHECK(voting_type IN ('yes_no', 'multi_choice', 'approval', 'budget')), -- Type of voting if enabled
  voting_options TEXT, -- JSON array of options for multi_choice
  requires_quorum INTEGER DEFAULT 0,
  quorum_percentage DECIMAL(5,2) DEFAULT 50.00,
  is_approved INTEGER DEFAULT 0,
  display_order INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE
);

-- Toplantı oyları
CREATE TABLE IF NOT EXISTS meeting_votes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  meeting_id INTEGER NOT NULL,
  topic_id INTEGER NOT NULL,
  attendee_id INTEGER NOT NULL, -- meeting_attendees.id
  vote_value TEXT NOT NULL, -- 'yes', 'no', 'abstain', or option index for multi_choice
  vote_weight DECIMAL(5,2) DEFAULT 1.0, -- Copied from attendee's vote_weight for historical integrity
  comment TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY(meeting_id) REFERENCES building_meetings(id) ON DELETE CASCADE,
  FOREIGN KEY(topic_id) REFERENCES meeting_topics(id) ON DELETE CASCADE,
  FOREIGN KEY(attendee_id) REFERENCES meeting_attendees(id) ON DELETE CASCADE,
  UNIQUE(meeting_id, topic_id, attendee_id) -- One vote per attendee per topic
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_topics_meeting_id ON meeting_topics(meeting_id);
CREATE INDEX IF NOT EXISTS idx_topics_voting_enabled ON meeting_topics(voting_enabled);
CREATE INDEX IF NOT EXISTS idx_topics_display_order ON meeting_topics(meeting_id, display_order);
CREATE INDEX IF NOT EXISTS idx_votes_meeting_id ON meeting_votes(meeting_id);
CREATE INDEX IF NOT EXISTS idx_votes_topic_id ON meeting_votes(topic_id);
CREATE INDEX IF NOT EXISTS idx_votes_attendee_id ON meeting_votes(attendee_id);

