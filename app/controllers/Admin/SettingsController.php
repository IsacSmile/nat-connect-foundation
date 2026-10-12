<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ImageUploader;
use App\Core\View;
use App\Models\SettingModel;
use Throwable;

class SettingsController
{
    /**
     * Display the Admin Settings page.
     */
    public function show(): void
    {
        Auth::requireAuth('/admin/settings');

        $settings = SettingModel::getAll();
        $errors   = $_SESSION['flash_errors'] ?? [];
        $old      = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        // Merge old input with saved settings
        if (!empty($old)) {
            $settings = array_merge($settings, $old);
        }

        $currentTab = (string) ($_GET['tab'] ?? 'general');

        View::render('admin/settings', [
            'pageTitle'  => 'Settings — NatConnect Foundation',
            'activeNav'  => 'settings',
            'settings'   => $settings,
            'errors'     => $errors,
            'currentTab' => $currentTab,
        ], 'layouts/admin');
    }

    /**
     * Update and persist site settings.
     */
    public function save(): void
    {
        Auth::requireAuth('/admin/settings');
        Csrf::verify($_POST['csrf_token'] ?? null);

        $errors = [];
        $currentSettings = SettingModel::getAll();

        // 1. Site details
        $siteName         = trim((string) ($_POST['site_name'] ?? ''));
        $siteTagline      = trim((string) ($_POST['site_tagline'] ?? ''));
        $missionStatement = trim((string) ($_POST['mission_statement'] ?? ''));
        $footerAbout      = trim((string) ($_POST['footer_about'] ?? ''));

        if ($siteName === '') {
            $errors['site_name'] = 'Site name is required.';
        } elseif (mb_strlen($siteName) > 150) {
            $errors['site_name'] = 'Site name cannot exceed 150 characters.';
        }

        // 2. Contact details
        $contactEmail    = trim((string) ($_POST['contact_email'] ?? ''));
        $contactPhone    = trim((string) ($_POST['contact_phone'] ?? ''));
        $contactWhatsapp = trim((string) ($_POST['contact_whatsapp'] ?? ''));
        $contactAddress  = trim((string) ($_POST['contact_address'] ?? ''));

        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Please enter a valid email address.';
        }

        // 3. Social URLs (validate as https:// URLs if provided)
        $socialFields = [
            'social_facebook'  => 'Facebook',
            'social_instagram' => 'Instagram',
            'social_x'         => 'X',
            'social_youtube'   => 'YouTube',
            'social_linkedin'  => 'LinkedIn',
        ];

        $socialValues = [];
        foreach ($socialFields as $field => $label) {
            $val = trim((string) ($_POST[$field] ?? ''));
            if ($val !== '') {
                if (!str_starts_with($val, 'https://') || !filter_var($val, FILTER_VALIDATE_URL)) {
                    $errors[$field] = "{$label} URL must be a valid https:// web address.";
                }
            }
            $socialValues[$field] = $val;
        }

        // 4. Donate settings
        $donateIntro       = trim((string) ($_POST['donate_intro'] ?? ''));
        $donateUpiId       = trim((string) ($_POST['donate_upi_id'] ?? ''));
        $donateBankDetails = trim((string) ($_POST['donate_bank_details'] ?? ''));
        $donateUpiQr       = (string) ($currentSettings['donate_upi_qr'] ?? '');

        // Handle QR code removal if requested
        if (!empty($_POST['remove_upi_qr'])) {
            if ($donateUpiQr !== '') {
                try {
                    ImageUploader::deleteVariants($donateUpiQr);
                } catch (Throwable) {
                    // Suppress file delete errors
                }
                $donateUpiQr = '';
            }
        }

        // Handle QR code file upload
        if (isset($_FILES['upi_qr_file']) && (int) $_FILES['upi_qr_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadRes = ImageUploader::upload($_FILES['upi_qr_file']);
                // Remove previous QR variants if replacing
                if ($donateUpiQr !== '') {
                    try {
                        ImageUploader::deleteVariants($donateUpiQr);
                    } catch (Throwable) {
                        // Suppress file delete errors
                    }
                }
                $donateUpiQr = $uploadRes['location'];
            } catch (Throwable $e) {
                $errors['donate_upi_qr'] = $e->getMessage();
            }
        }

        $tab = trim((string) ($_POST['_tab'] ?? 'general'));
        if (!in_array($tab, ['general', 'home'], true)) {
            $tab = 'general';
        }

        $isHomeTab = ($tab === 'home');

        // About band photo (ImageUploader + required alt)
        $aboutBandPhoto = (string) ($currentSettings['about_band_photo'] ?? '');
        $aboutBandPhotoAlt = trim((string) ($_POST['about_band_photo_alt'] ?? ($currentSettings['about_band_photo_alt'] ?? '')));

        if (!empty($_POST['remove_about_band_photo'])) {
            if ($aboutBandPhoto !== '') {
                try {
                    ImageUploader::deleteVariants($aboutBandPhoto);
                } catch (Throwable) {
                    // Suppress file delete errors
                }
                $aboutBandPhoto = '';
                $aboutBandPhotoAlt = '';
            }
        }

        if (isset($_FILES['about_band_photo_file']) && (int) $_FILES['about_band_photo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadRes = ImageUploader::upload($_FILES['about_band_photo_file']);
                if ($aboutBandPhoto !== '') {
                    try {
                        ImageUploader::deleteVariants($aboutBandPhoto);
                    } catch (Throwable) {
                        // Suppress file delete errors
                    }
                }
                $aboutBandPhoto = $uploadRes['location'];
            } catch (Throwable $e) {
                $errors['about_band_photo'] = $e->getMessage();
            }
        }

        if ($aboutBandPhoto !== '') {
            if ($aboutBandPhotoAlt === '') {
                $errors['about_band_photo_alt'] = 'Alternative text is required for the About band photo.';
            } elseif (preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $aboutBandPhotoAlt)) {
                $errors['about_band_photo_alt'] = 'Alternative text cannot be a file name.';
            } elseif (mb_strlen($aboutBandPhotoAlt) < 3) {
                $errors['about_band_photo_alt'] = 'Alternative text must be at least 3 characters.';
            }
        }

        // 5. Home Page settings
        $announcementText = trim((string) ($_POST['announcement_text'] ?? ''));
        $announcementLink = trim((string) ($_POST['announcement_link'] ?? ''));
        if (mb_strlen($announcementText) > 120) {
            $errors['announcement_text'] = 'Announcement text cannot exceed 120 characters.';
        }

        $homeHeroHeadline = trim((string) ($_POST['home_hero_headline'] ?? ''));
        if (mb_strlen($homeHeroHeadline) > 80) {
            $errors['home_hero_headline'] = 'Hero headline cannot exceed 80 characters.';
        }

        $homeHeroIntro = trim((string) ($_POST['home_hero_intro'] ?? ''));
        if (mb_strlen($homeHeroIntro) > 200) {
            $errors['home_hero_intro'] = 'Hero intro cannot exceed 200 characters.';
        }

        // Hero slider auto-rotate
        $heroSliderAutorotate = $isHomeTab ? (!empty($_POST['hero_slider_autorotate']) ? '1' : '0') : ($currentSettings['hero_slider_autorotate'] ?? '0');

        // Impact numbers: up to 4 pairs (number max 12 chars, label max 40 chars)
        $impactNumbersUpdates = [];
        for ($i = 1; $i <= 4; $i++) {
            $numKey = "home_impact_number_{$i}";
            $lblKey = "home_impact_label_{$i}";
            $num = $isHomeTab ? trim((string) ($_POST[$numKey] ?? '')) : (string) ($currentSettings[$numKey] ?? '');
            $lbl = $isHomeTab ? trim((string) ($_POST[$lblKey] ?? '')) : (string) ($currentSettings[$lblKey] ?? '');

            if (mb_strlen($num) > 12) {
                $errors[$numKey] = "Impact number {$i} cannot exceed 12 characters.";
            }
            if (mb_strlen($lbl) > 40) {
                $errors[$lblKey] = "Impact label {$i} cannot exceed 40 characters.";
            }

            $impactNumbersUpdates[$numKey] = $num;
            $impactNumbersUpdates[$lblKey] = $lbl;
        }

        // Get involved section: title, show/hide, 3 cards
        $sectionShowGetInvolved = $isHomeTab ? (!empty($_POST['section_show_get_involved']) ? '1' : '0') : ($currentSettings['section_show_get_involved'] ?? '1');
        $sectionTitleGetInvolved = trim((string) ($_POST['section_title_get_involved'] ?? '')) ?: 'Get involved';

        $cardUpdates = [];
        for ($i = 1; $i <= 3; $i++) {
            $cTitleKey = "home_card_{$i}_title";
            $cTextKey  = "home_card_{$i}_text";
            $cBtnKey   = "home_card_{$i}_btn_label";
            $cLinkKey  = "home_card_{$i}_link";
            $cWaKey    = "home_card_{$i}_wa_message";

            $cTitle = $isHomeTab ? trim((string) ($_POST[$cTitleKey] ?? '')) : (string) ($currentSettings[$cTitleKey] ?? '');
            $cText  = $isHomeTab ? trim((string) ($_POST[$cTextKey] ?? '')) : (string) ($currentSettings[$cTextKey] ?? '');
            $cBtn   = $isHomeTab ? trim((string) ($_POST[$cBtnKey] ?? '')) : (string) ($currentSettings[$cBtnKey] ?? '');
            $cLink  = $isHomeTab ? trim((string) ($_POST[$cLinkKey] ?? '')) : (string) ($currentSettings[$cLinkKey] ?? '');
            $cWa    = $isHomeTab ? trim((string) ($_POST[$cWaKey] ?? '')) : (string) ($currentSettings[$cWaKey] ?? '');

            if (mb_strlen($cText) > 140) {
                $errors[$cTextKey] = "Card {$i} text cannot exceed 140 characters.";
            }

            $cardUpdates[$cTitleKey] = $cTitle;
            $cardUpdates[$cTextKey]  = $cText;
            $cardUpdates[$cBtnKey]   = $cBtn;
            $cardUpdates[$cLinkKey]  = $cLink;
            $cardUpdates[$cWaKey]    = $cWa;
        }

        $sectionIntroRti = trim((string) ($_POST['section_intro_rti'] ?? ''));
        if (mb_strlen($sectionIntroRti) > 200) {
            $errors['section_intro_rti'] = 'RTI section intro cannot exceed 200 characters.';
        }

        $sectionIntroNewsletter = trim((string) ($_POST['section_intro_newsletter'] ?? ''));
        if (mb_strlen($sectionIntroNewsletter) > 160) {
            $errors['section_intro_newsletter'] = 'Newsletter intro cannot exceed 160 characters.';
        }

        $sectionIntroDonate = trim((string) ($_POST['section_intro_donate'] ?? ''));
        if (mb_strlen($sectionIntroDonate) > 160) {
            $errors['section_intro_donate'] = 'Donate band text cannot exceed 160 characters.';
        }

        $sectionTitleLatest = trim((string) ($_POST['section_title_latest'] ?? '')) ?: 'Latest stories';
        $sectionTitleTopics = trim((string) ($_POST['section_title_topics'] ?? '')) ?: 'Explore by topic';
        $sectionTitleCauses = trim((string) ($_POST['section_title_causes'] ?? '')) ?: 'Causes we care about';
        $sectionTitleCampaigns = trim((string) ($_POST['section_title_campaigns'] ?? '')) ?: 'Campaigns';
        $sectionTitleRti = trim((string) ($_POST['section_title_rti'] ?? '')) ?: 'Right to Information';
        $sectionTitleGallery = trim((string) ($_POST['section_title_gallery'] ?? '')) ?: 'From the gallery';
        $sectionTitleNewsletter = trim((string) ($_POST['section_title_newsletter'] ?? '')) ?: 'Stay informed';
        $sectionTitleDonate = trim((string) ($_POST['section_title_donate'] ?? '')) ?: 'Support our work';

        $sectionSubtitleLatest    = trim((string) ($_POST['section_subtitle_latest'] ?? ''));
        $sectionSubtitleTopics    = trim((string) ($_POST['section_subtitle_topics'] ?? ''));
        $sectionSubtitleCauses    = trim((string) ($_POST['section_subtitle_causes'] ?? ''));
        $sectionSubtitleCampaigns = trim((string) ($_POST['section_subtitle_campaigns'] ?? ''));
        $sectionSubtitleRti       = trim((string) ($_POST['section_subtitle_rti'] ?? ''));
        $sectionSubtitleGallery   = trim((string) ($_POST['section_subtitle_gallery'] ?? ''));
        $sectionSubtitleNewsletter= trim((string) ($_POST['section_subtitle_newsletter'] ?? ''));
        $galleryIntro             = trim((string) ($_POST['gallery_intro'] ?? ''));

        // Show/hide toggles: only update if on 'home' tab to prevent wiping toggles when saving 'general' tab
        $sectionShowAnnouncement = $isHomeTab ? (!empty($_POST['section_show_announcement']) ? '1' : '0') : ($currentSettings['section_show_announcement'] ?? '0');
        $sectionShowLatest       = $isHomeTab ? (!empty($_POST['section_show_latest']) ? '1' : '0') : ($currentSettings['section_show_latest'] ?? '1');
        $sectionShowTopics       = $isHomeTab ? (!empty($_POST['section_show_topics']) ? '1' : '0') : ($currentSettings['section_show_topics'] ?? '1');
        $sectionShowCauses       = $isHomeTab ? (!empty($_POST['section_show_causes']) ? '1' : '0') : ($currentSettings['section_show_causes'] ?? '1');
        $sectionShowCampaigns    = $isHomeTab ? (!empty($_POST['section_show_campaigns']) ? '1' : '0') : ($currentSettings['section_show_campaigns'] ?? '1');
        $sectionShowRti          = $isHomeTab ? (!empty($_POST['section_show_rti']) ? '1' : '0') : ($currentSettings['section_show_rti'] ?? '1');
        $sectionShowGallery      = $isHomeTab ? (!empty($_POST['section_show_gallery']) ? '1' : '0') : ($currentSettings['section_show_gallery'] ?? '1');
        $sectionShowNewsletter   = $isHomeTab ? (!empty($_POST['section_show_newsletter']) ? '1' : '0') : ($currentSettings['section_show_newsletter'] ?? '1');
        $sectionShowDonate       = $isHomeTab ? (!empty($_POST['section_show_donate']) ? '1' : '0') : ($currentSettings['section_show_donate'] ?? '1');

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old']    = $_POST;
            header('Location: /admin/settings?tab=' . urlencode($tab));
            exit;
        }

        // Prepare key-value updates
        $updates = [
            'site_name'                    => $siteName,
            'site_tagline'                 => $siteTagline,
            'mission_statement'            => $missionStatement,
            'footer_about'                 => $footerAbout,
            'contact_email'                => $contactEmail,
            'contact_phone'                => $contactPhone,
            'contact_whatsapp'             => $contactWhatsapp,
            'contact_address'              => $contactAddress,
            'donate_intro'                 => $donateIntro,
            'donate_upi_id'                => $donateUpiId,
            'donate_upi_qr'                => $donateUpiQr,
            'donate_bank_details'          => $donateBankDetails,
            'about_band_photo'             => $aboutBandPhoto,
            'about_band_photo_alt'         => $aboutBandPhotoAlt,
            'announcement_text'            => $announcementText,
            'announcement_link'            => $announcementLink,
            'home_hero_headline'           => $homeHeroHeadline,
            'home_hero_intro'              => $homeHeroIntro,
            'hero_slider_autorotate'       => $heroSliderAutorotate,
            'section_show_get_involved'    => $sectionShowGetInvolved,
            'section_title_get_involved'   => $sectionTitleGetInvolved,
            'section_title_latest'         => $sectionTitleLatest,
            'section_title_topics'         => $sectionTitleTopics,
            'section_title_causes'         => $sectionTitleCauses,
            'section_title_campaigns'      => $sectionTitleCampaigns,
            'section_title_rti'            => $sectionTitleRti,
            'section_title_gallery'        => $sectionTitleGallery,
            'section_title_newsletter'     => $sectionTitleNewsletter,
            'section_title_donate'         => $sectionTitleDonate,
            'section_subtitle_latest'      => $sectionSubtitleLatest,
            'section_subtitle_topics'      => $sectionSubtitleTopics,
            'section_subtitle_causes'      => $sectionSubtitleCauses,
            'section_subtitle_campaigns'   => $sectionSubtitleCampaigns,
            'section_subtitle_rti'         => $sectionSubtitleRti,
            'section_subtitle_gallery'     => $sectionSubtitleGallery,
            'section_subtitle_newsletter'  => $sectionSubtitleNewsletter,
            'gallery_intro'                => $galleryIntro,
            'section_intro_rti'            => $sectionIntroRti,
            'section_intro_newsletter'     => $sectionIntroNewsletter,
            'section_intro_donate'         => $sectionIntroDonate,
            'section_show_announcement'    => $sectionShowAnnouncement,
            'section_show_latest'          => $sectionShowLatest,
            'section_show_topics'          => $sectionShowTopics,
            'section_show_causes'          => $sectionShowCauses,
            'section_show_campaigns'       => $sectionShowCampaigns,
            'section_show_rti'             => $sectionShowRti,
            'section_show_gallery'         => $sectionShowGallery,
            'section_show_newsletter'      => $sectionShowNewsletter,
            'section_show_donate'          => $sectionShowDonate,
        ];

        foreach ($impactNumbersUpdates as $k => $v) {
            $updates[$k] = $v;
        }

        foreach ($cardUpdates as $k => $v) {
            $updates[$k] = $v;
        }

        foreach ($socialValues as $field => $val) {
            $updates[$field] = $val;
        }

        SettingModel::setMany($updates);

        $_SESSION['flash_success'] = 'Settings saved successfully.';

        header('Location: /admin/settings?tab=' . urlencode($tab));
        exit;
    }
}
