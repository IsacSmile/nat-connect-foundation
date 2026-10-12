-- Migration 010: Home Page Round 2 Settings
-- Adds hero slider auto-rotate setting, impact numbers, get involved section, and about band photo settings

INSERT INTO settings (`key`, `value`) VALUES
    ('hero_slider_autorotate', '0'),
    ('home_impact_number_1', ''),
    ('home_impact_label_1', ''),
    ('home_impact_number_2', ''),
    ('home_impact_label_2', ''),
    ('home_impact_number_3', ''),
    ('home_impact_label_3', ''),
    ('home_impact_number_4', ''),
    ('home_impact_label_4', ''),
    ('section_show_get_involved', '1'),
    ('section_title_get_involved', 'Get involved'),
    ('home_card_1_title', 'Volunteer with us'),
    ('home_card_1_text', 'Join our community conservation efforts and ground initiatives to protect nature and local ecosystems.'),
    ('home_card_1_btn_label', 'Volunteer'),
    ('home_card_1_link', '/contact'),
    ('home_card_1_wa_message', ''),
    ('home_card_2_title', 'Report an environmental concern'),
    ('home_card_2_text', 'Notice illegal wetland damage or coastal violations? Send details to help us investigate and document.'),
    ('home_card_2_btn_label', 'Report concern'),
    ('home_card_2_link', '/contact'),
    ('home_card_2_wa_message', ''),
    ('home_card_3_title', 'Share a story'),
    ('home_card_3_text', 'Have local environmental news, observations, or RTI findings? Share your story with our editorial team.'),
    ('home_card_3_btn_label', 'Share story'),
    ('home_card_3_link', '/contact'),
    ('home_card_3_wa_message', ''),
    ('about_band_photo', ''),
    ('about_band_photo_alt', '')
ON DUPLICATE KEY UPDATE `key` = `key`;
