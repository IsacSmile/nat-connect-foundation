-- Migration 012: Get Involved Settings & Plain Copy Defaults
-- Adds get_involved_intro, get_involved_photo, get_involved_photo_alt settings
-- Updates default card texts to avoid claims about investigating or editorial teams.

INSERT INTO settings (`key`, `value`) VALUES
    ('get_involved_intro', ''),
    ('get_involved_photo', ''),
    ('get_involved_photo_alt', '')
ON DUPLICATE KEY UPDATE `key` = `key`;

UPDATE settings 
SET `value` = 'Give your time and skills to campaigns, drives and awareness work.' 
WHERE `key` = 'home_card_1_text' 
  AND `value` = 'Join our community conservation efforts and ground initiatives to protect nature and local ecosystems.';

UPDATE settings 
SET `value` = 'Seen damage to a wetland, mangrove or trees in your area? Tell us what you saw.' 
WHERE `key` = 'home_card_2_text' 
  AND `value` = 'Notice illegal wetland damage or coastal violations? Send details to help us investigate and document.';

UPDATE settings 
SET `value` = 'Have a local environmental story, photo or observation? Send it to us.' 
WHERE `key` = 'home_card_3_text' 
  AND `value` = 'Have local environmental news, observations, or RTI findings? Share your story with our editorial team.';
