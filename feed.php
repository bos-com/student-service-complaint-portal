<?php
session_start();

if (!isset($_SESSION['student'])) {
    header('Location: index.php#login');
    exit;
}
$student = $_SESSION['student'];
$studentId = $student['id'];

if (($student['type'] ?? '') === 'admin') {
    header('Location: admindashboard.php');
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------- DATABASE ---------- */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}

/* ---------- LANGUAGE SETUP ---------- */
$supported_languages = ['en', 'sw', 'fr', 'lg'];
$current_language = $_SESSION['language'] ?? 'en';

// Check if language is being changed
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages)) {
    $current_language = $_GET['lang'];
    $_SESSION['language'] = $current_language;
}

/* ---------- LANGUAGE TRANSLATIONS ---------- */
$translations = [
    'en' => [
        'title' => 'Feed | CampusVoice',
        'brand_name' => 'CampusVoice',
        'brand_subtitle' => 'Bugema University',
        'notifications' => 'Notifications',
        'toggle_theme' => 'Toggle Theme',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'greeting' => 'What\'s your complaint today?',
        'tab_all' => 'All',
        'tab_pending' => 'Pending',
        'tab_progress' => 'In Progress',
        'tab_resolved' => 'Resolved',
        'loading_complaints' => 'Loading complaints...',
        'current_feed' => 'Current Feed',
        'trending_issues' => 'Trending Issues',
        'no_complaints' => 'No complaints found',
        'loading' => 'Loading...',
        'lodge_complaint' => 'Lodge Complaint',
        'post_anonymously' => 'Post Anonymously',
        'title_label' => 'Title',
        'title_placeholder' => 'Brief description of your complaint',
        'category_label' => 'Category',
        'category_placeholder' => 'Select category',
        'category_infrastructure' => 'Infrastructure',
        'category_it' => 'IT & Network',
        'category_accommodation' => 'Accommodation',
        'category_food' => 'Food & Catering',
        'category_academic' => 'Academic',
        'category_security' => 'Security',
        'category_other' => 'Other',
        'description_label' => 'Description',
        'description_placeholder' => 'Explain your complaint in detail...',
        'location_label' => 'Location',
        'location_placeholder' => 'Where did this happen? (optional)',
        'priority_label' => 'Priority Level',
        'priority_low' => 'Low',
        'priority_medium' => 'Medium',
        'priority_high' => 'High',
        'attach_evidence' => 'Attach Evidence',
        'upload_text' => 'Click to upload image or PDF',
        'upload_max' => 'Max 5MB',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
        'my_profile' => 'My Profile',
        'complaints' => 'Complaints',
        'resolved' => 'Resolved',
        'success_rate' => 'Success Rate',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'rate_service' => 'Rate Our Service',
        'rate_help' => 'Help us improve by rating your experience',
        'account_settings' => 'Account Settings',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'security_questions' => 'Security Questions',
        'security_questions_help' => 'Set up security questions for password recovery',
        'manage_security_questions' => 'Manage Security Questions',
        'save_changes' => 'Save Changes',
        'danger_zone' => 'Danger Zone',
        'rate_our_service' => 'Rate Our Service',
        'select_complaint' => 'Select Resolved Complaint',
        'choose_complaint' => 'Choose a resolved complaint...',
        'no_resolved_complaints' => 'No resolved complaints available for rating yet.',
        'overall_rating' => 'Overall Rating',
        'tap_to_rate' => 'Tap a star to rate',
        'response_time' => 'Response Time',
        'response_excellent' => 'Excellent (< 1 day)',
        'response_good' => 'Good (1-3 days)',
        'response_fair' => 'Fair (3-7 days)',
        'response_poor' => 'Poor (> 7 days)',
        'resolution_quality' => 'Resolution Quality',
        'quality_excellent' => 'Excellent - Completely solved',
        'quality_good' => 'Good - Mostly solved',
        'quality_fair' => 'Fair - Partially solved',
        'quality_poor' => 'Poor - Not solved',
        'would_recommend' => 'Would you recommend CampusVoice?',
        'feedback_label' => 'Additional Feedback (Optional)',
        'feedback_placeholder' => 'Tell us more about your experience...',
        'submit_rating' => 'Submit Rating',
        'share_complaint' => 'Share Complaint',
        'share_whatsapp' => 'WhatsApp',
        'share_twitter' => 'Twitter',
        'share_facebook' => 'Facebook',
        'share_copy' => 'Copy Link',
        'share_email' => 'Email',
        'share_block' => 'Block',
        'nav_feed' => 'Feed',
        'nav_post' => 'Post',
        'nav_alerts' => 'Alerts',
        'nav_profile' => 'Profile',
        'anonymous' => 'Anonymous',
        'user' => 'User',
        'copy_link' => 'Link copied',
        'user_blocked' => 'User blocked',
        'profile_updated' => 'Profile updated',
        'login_required' => 'Name required',
        'logout_confirm' => 'Logout?',
        'block_confirm' => 'Block this user?',
        'comment_placeholder' => 'Write a comment...',
        'under_review' => 'Your complaint is under review.',
        'comment_posted' => 'Comment posted',
        'liked' => 'liked',
        'comments' => 'comments',
        'share' => 'Share',
        'now' => 'now',
        'minutes' => 'm',
        'hours' => 'h',
        'days' => 'd',
        'language' => 'Language',
        'english' => 'English',
        'swahili' => 'Swahili',
        'french' => 'French',
        'luganda' => 'Luganda',
        'please_fill_all_questions' => 'Please fill all questions and answers',
        'select_different_questions' => 'Select different questions for each',
        'security_questions_saved' => 'Security questions saved successfully',
        'error_loading_security' => 'Error loading security questions',
        'error_saving_security' => 'Error saving security questions',
        'saving' => 'Saving',
        'save_security_questions' => 'Save Security Questions',
        'submitting' => 'Submitting',
        'thank_you_feedback' => 'Thank you for your feedback!',
        'connection_error' => 'Connection error. Please try again.'
    ],
    'sw' => [
        'title' => 'Ujumbe | CampusVoice',
        'brand_name' => 'CampusVoice',
        'brand_subtitle' => 'Chuo Kikuu cha Bugema',
        'notifications' => 'Arifa',
        'toggle_theme' => 'Badilisha Mandhari',
        'profile' => 'Wasifu',
        'logout' => 'Toka',
        'greeting' => 'Una nini cha kulalamikia leo?',
        'tab_all' => 'Zote',
        'tab_pending' => 'Inasubiri',
        'tab_progress' => 'Inaendelea',
        'tab_resolved' => 'Imetatuliwa',
        'loading_complaints' => 'Inapakia malalamiko...',
        'current_feed' => 'Ujumbe wa Sasa',
        'trending_issues' => 'Masuala Maarufu',
        'no_complaints' => 'Hakuna malalamiko yaliyopatikana',
        'loading' => 'Inapakia...',
        'lodge_complaint' => 'Wasilisha Malalamiko',
        'post_anonymously' => 'Tuma Kwa Kujitolea',
        'title_label' => 'Kichwa',
        'title_placeholder' => 'Maelezo mafupi ya malalamiko yako',
        'category_label' => 'Kategoria',
        'category_placeholder' => 'Chagua kategoria',
        'category_infrastructure' => 'Miundombinu',
        'category_it' => 'IT & Mtandao',
        'category_accommodation' => 'Makazi',
        'category_food' => 'Chakula',
        'category_academic' => 'Kielimu',
        'category_security' => 'Usalama',
        'category_other' => 'Nyingine',
        'description_label' => 'Maelezo',
        'description_placeholder' => 'Eleza malalamiko yako kwa kina...',
        'location_label' => 'Mahali',
        'location_placeholder' => 'Imetokea wapi? (hiari)',
        'priority_label' => 'Kipaumbele',
        'priority_low' => 'Chini',
        'priority_medium' => 'Wastani',
        'priority_high' => 'Juu',
        'attach_evidence' => 'Ambatisha Ushahidi',
        'upload_text' => 'Bonyeza kupakia picha au PDF',
        'upload_max' => 'Kiwango cha juu 5MB',
        'cancel' => 'Ghairi',
        'submit' => 'Wasilisha',
        'my_profile' => 'Wasifu Wangu',
        'complaints' => 'Malalamiko',
        'resolved' => 'Yaliyotatuliwa',
        'success_rate' => 'Kiwango cha Mafanikio',
        'pending' => 'Inasubiri',
        'in_progress' => 'Inaendelea',
        'rate_service' => 'Tathmini Huduma Yetu',
        'rate_help' => 'Tusaidie kuboresha kwa kutathmini uzoefu wako',
        'account_settings' => 'Mipangilio ya Akaunti',
        'full_name' => 'Jina Kamili',
        'email' => 'Barua Pepe',
        'phone' => 'Simu',
        'security_questions' => 'Maswali ya Usalama',
        'security_questions_help' => 'Weka maswali ya usalama kwa ajili ya kurejesha nenosiri',
        'manage_security_questions' => 'Dhibiti Maswali ya Usalama',
        'save_changes' => 'Hifadhi Mabadiliko',
        'danger_zone' => 'Eneo la Hatari',
        'rate_our_service' => 'Tathmini Huduma Yetu',
        'select_complaint' => 'Chagua Malalamiko Yaliyotatuliwa',
        'choose_complaint' => 'Chagua malalamiko yaliyotatuliwa...',
        'no_resolved_complaints' => 'Hakuna malalamiko yaliyotatuliwa yanayopatikana kwa ajili ya tathmini bado.',
        'overall_rating' => 'Tathmini ya Jumla',
        'tap_to_rate' => 'Gusa nyota kutathmini',
        'response_time' => 'Muda wa Majibu',
        'response_excellent' => 'Bora sana (< siku 1)',
        'response_good' => 'Nzuri (siku 1-3)',
        'response_fair' => 'Wastani (siku 3-7)',
        'response_poor' => 'Duni (> siku 7)',
        'resolution_quality' => 'Ubora wa Ufumbuzi',
        'quality_excellent' => 'Bora - Imetatuliwa kabisa',
        'quality_good' => 'Nzuri - Imetatuliwa kwa kiasi kikubwa',
        'quality_fair' => 'Wastani - Imetatuliwa kwa sehemu',
        'quality_poor' => 'Duni - Haijatatuliwa',
        'would_recommend' => 'Ungeipendekeza CampusVoice?',
        'feedback_label' => 'Maoni ya Ziada (Hiari)',
        'feedback_placeholder' => 'Tuambie zaidi kuhusu uzoefu wako...',
        'submit_rating' => 'Wasilisha Tathmini',
        'share_complaint' => 'Shiriki Malalamiko',
        'share_whatsapp' => 'WhatsApp',
        'share_twitter' => 'Twitter',
        'share_facebook' => 'Facebook',
        'share_copy' => 'Nakili Kiungo',
        'share_email' => 'Barua Pepe',
        'share_block' => 'Zuia',
        'nav_feed' => 'Ujumbe',
        'nav_post' => 'Tuma',
        'nav_alerts' => 'Arifa',
        'nav_profile' => 'Wasifu',
        'anonymous' => 'Mjasiri',
        'user' => 'Mtumiaji',
        'copy_link' => 'Kiungo kimenakiliwa',
        'user_blocked' => 'Mtumiaji amezuiwa',
        'profile_updated' => 'Wasifu umevumbuliwa',
        'login_required' => 'Jina linahitajika',
        'logout_confirm' => 'Toka?',
        'block_confirm' => 'Zuia mtumiaji huyu?',
        'comment_placeholder' => 'Andika maoni...',
        'under_review' => 'Malalamiko yako yanakaguliwa.',
        'comment_posted' => 'Maoni yamewasilishwa',
        'liked' => 'amependa',
        'comments' => 'maoni',
        'share' => 'Shiriki',
        'now' => 'sasa',
        'minutes' => 'dak',
        'hours' => 'saa',
        'days' => 'siku',
        'language' => 'Lugha',
        'english' => 'Kiswahili',
        'swahili' => 'Kiswahili',
        'french' => 'Kifaransa',
        'luganda' => 'Luganda',
        'please_fill_all_questions' => 'Tafadhali jaza maswali na majibu yote',
        'select_different_questions' => 'Chagua maswali tofauti kwa kila mmoja',
        'security_questions_saved' => 'Maswali ya usalama yamehifadhiwa kikamilifu',
        'error_loading_security' => 'Hitilafu katika kupakia maswali ya usalama',
        'error_saving_security' => 'Hitilafu katika kuhifadhi maswali ya usalama',
        'saving' => 'Inahifadhi',
        'save_security_questions' => 'Hifadhi Maswali ya Usalama',
        'submitting' => 'Inawasilisha',
        'thank_you_feedback' => 'Asante kwa maoni yako!',
        'connection_error' => 'Hitilafu ya muunganisho. Tafadhali jaribu tena.'
    ],
    'fr' => [
        'title' => 'Fil d\'actualité | CampusVoice',
        'brand_name' => 'CampusVoice',
        'brand_subtitle' => 'Université Bugema',
        'notifications' => 'Notifications',
        'toggle_theme' => 'Changer le thème',
        'profile' => 'Profil',
        'logout' => 'Déconnexion',
        'greeting' => 'Quelle est votre plainte aujourd\'hui ?',
        'tab_all' => 'Tous',
        'tab_pending' => 'En attente',
        'tab_progress' => 'En cours',
        'tab_resolved' => 'Résolu',
        'loading_complaints' => 'Chargement des plaintes...',
        'current_feed' => 'Fil actuel',
        'trending_issues' => 'Problèmes tendances',
        'no_complaints' => 'Aucune plainte trouvée',
        'loading' => 'Chargement...',
        'lodge_complaint' => 'Déposer une plainte',
        'post_anonymously' => 'Publier anonymement',
        'title_label' => 'Titre',
        'title_placeholder' => 'Brève description de votre plainte',
        'category_label' => 'Catégorie',
        'category_placeholder' => 'Sélectionner une catégorie',
        'category_infrastructure' => 'Infrastructure',
        'category_it' => 'IT & Réseau',
        'category_accommodation' => 'Logement',
        'category_food' => 'Nourriture',
        'category_academic' => 'Académique',
        'category_security' => 'Sécurité',
        'category_other' => 'Autre',
        'description_label' => 'Description',
        'description_placeholder' => 'Expliquez votre plainte en détail...',
        'location_label' => 'Lieu',
        'location_placeholder' => 'Où cela s\'est-il passé ? (optionnel)',
        'priority_label' => 'Niveau de priorité',
        'priority_low' => 'Basse',
        'priority_medium' => 'Moyenne',
        'priority_high' => 'Haute',
        'attach_evidence' => 'Joindre des preuves',
        'upload_text' => 'Cliquez pour télécharger une image ou un PDF',
        'upload_max' => 'Max 5MB',
        'cancel' => 'Annuler',
        'submit' => 'Soumettre',
        'my_profile' => 'Mon Profil',
        'complaints' => 'Plaintes',
        'resolved' => 'Résolues',
        'success_rate' => 'Taux de réussite',
        'pending' => 'En attente',
        'in_progress' => 'En cours',
        'rate_service' => 'Évaluer notre service',
        'rate_help' => 'Aidez-nous à nous améliorer en évaluant votre expérience',
        'account_settings' => 'Paramètres du compte',
        'full_name' => 'Nom complet',
        'email' => 'Email',
        'phone' => 'Téléphone',
        'security_questions' => 'Questions de sécurité',
        'security_questions_help' => 'Configurer les questions de sécurité pour la récupération du mot de passe',
        'manage_security_questions' => 'Gérer les questions de sécurité',
        'save_changes' => 'Enregistrer les modifications',
        'danger_zone' => 'Zone de danger',
        'rate_our_service' => 'Évaluer notre service',
        'select_complaint' => 'Sélectionner une plainte résolue',
        'choose_complaint' => 'Choisissez une plainte résolue...',
        'no_resolved_complaints' => 'Aucune plainte résolue disponible pour évaluation.',
        'overall_rating' => 'Évaluation globale',
        'tap_to_rate' => 'Appuyez sur une étoile pour évaluer',
        'response_time' => 'Temps de réponse',
        'response_excellent' => 'Excellent (< 1 jour)',
        'response_good' => 'Bon (1-3 jours)',
        'response_fair' => 'Correct (3-7 jours)',
        'response_poor' => 'Mauvais (> 7 jours)',
        'resolution_quality' => 'Qualité de la résolution',
        'quality_excellent' => 'Excellente - Complètement résolu',
        'quality_good' => 'Bonne - Principalement résolu',
        'quality_fair' => 'Correcte - Partiellement résolu',
        'quality_poor' => 'Mauvaise - Non résolu',
        'would_recommend' => 'Recommanderiez-vous CampusVoice ?',
        'feedback_label' => 'Retour supplémentaire (Optionnel)',
        'feedback_placeholder' => 'Dites-nous-en plus sur votre expérience...',
        'submit_rating' => 'Soumettre l\'évaluation',
        'share_complaint' => 'Partager la plainte',
        'share_whatsapp' => 'WhatsApp',
        'share_twitter' => 'Twitter',
        'share_facebook' => 'Facebook',
        'share_copy' => 'Copier le lien',
        'share_email' => 'Email',
        'share_block' => 'Bloquer',
        'nav_feed' => 'Fil',
        'nav_post' => 'Publier',
        'nav_alerts' => 'Alertes',
        'nav_profile' => 'Profil',
        'anonymous' => 'Anonyme',
        'user' => 'Utilisateur',
        'copy_link' => 'Lien copié',
        'user_blocked' => 'Utilisateur bloqué',
        'profile_updated' => 'Profil mis à jour',
        'login_required' => 'Nom requis',
        'logout_confirm' => 'Déconnexion ?',
        'block_confirm' => 'Bloquer cet utilisateur ?',
        'comment_placeholder' => 'Écrire un commentaire...',
        'under_review' => 'Votre plainte est en cours d\'examen.',
        'comment_posted' => 'Commentaire posté',
        'liked' => 'aimé',
        'comments' => 'commentaires',
        'share' => 'Partager',
        'now' => 'maintenant',
        'minutes' => 'min',
        'hours' => 'h',
        'days' => 'j',
        'language' => 'Langue',
        'english' => 'Anglais',
        'swahili' => 'Swahili',
        'french' => 'Français',
        'luganda' => 'Luganda',
        'please_fill_all_questions' => 'Veuillez remplir toutes les questions et réponses',
        'select_different_questions' => 'Sélectionnez des questions différentes pour chacune',
        'security_questions_saved' => 'Questions de sécurité enregistrées avec succès',
        'error_loading_security' => 'Erreur lors du chargement des questions de sécurité',
        'error_saving_security' => 'Erreur lors de l\'enregistrement des questions de sécurité',
        'saving' => 'Enregistrement',
        'save_security_questions' => 'Enregistrer les questions de sécurité',
        'submitting' => 'Envoi',
        'thank_you_feedback' => 'Merci pour vos commentaires !',
        'connection_error' => 'Erreur de connexion. Veuillez réessayer.'
    ],
    'lg' => [
        'title' => 'Ennyingo | CampusVoice',
        'brand_name' => 'CampusVoice',
        'brand_subtitle' => 'Yunivasite ya Bugema',
        'notifications' => 'Amawulire',
        'toggle_theme' => 'Kyusa Ennyingo',
        'profile' => 'Enfaanana',
        'logout' => 'Fuluma',
        'greeting' => 'Ki ky\'olina okuwulira leero?',
        'tab_all' => 'Byonna',
        'tab_pending' => 'Biri mu ntebe',
        'tab_progress' => 'Biri mu maaso',
        'tab_resolved' => 'Byeddembe',
        'loading_complaints' => 'Kuleeta ebikolwa...',
        'current_feed' => 'Ennyingo ey\'olubeerera',
        'trending_issues' => 'Ebizibu Ebiriwo',
        'no_complaints' => 'Tebalina bikolwa byonna',
        'loading' => 'Kuleeta...',
        'lodge_complaint' => 'Waayo Ekikolwa',
        'post_anonymously' => 'Waayo nga toli ludda',
        'title_label' => 'Entango',
        'title_placeholder' => 'Enkola entonotono y\'ekikolwa kyo',
        'category_label' => 'Enkola',
        'category_placeholder' => 'Londoola enkola',
        'category_infrastructure' => 'Emirimu gy\'okuzimba',
        'category_it' => 'IT & Mutimbagano',
        'category_accommodation' => 'Okutuula',
        'category_food' => 'Emmere',
        'category_academic' => 'Eby\'okusoma',
        'category_security' => 'Obwerinde',
        'category_other' => 'Endala',
        'description_label' => 'Enkola',
        'description_placeholder' => 'Wuliriza ekikolwa kyo mu bujjuvu...',
        'location_label' => 'Wali',
        'location_placeholder' => 'Wali kyo kyabaawo? (ky\'okwagala)',
        'priority_label' => 'Okusookera',
        'priority_low' => 'Wansi',
        'priority_medium' => 'Wakati',
        'priority_high' => 'Waggulu',
        'attach_evidence' => 'Weeyo Obujulizi',
        'upload_text' => 'Koona okweeyesa ekifaananyi oba PDF',
        'upload_max' => 'Kyenkana 5MB',
        'cancel' => 'Sazaamu',
        'submit' => 'Waayo',
        'my_profile' => 'Enfaanana yange',
        'complaints' => 'Ebikolwa',
        'resolved' => 'Ebyeddembe',
        'success_rate' => 'Enkola y\'Obulungi',
        'pending' => 'Mu ntebe',
        'in_progress' => 'Mu maaso',
        'rate_service' => 'Wandiisa Obwetaavu bwaffe',
        'rate_help' => 'Tuyambe okwongera okwandika ekifo kyaffe',
        'account_settings' => 'Eby\'okutegeka mu Account',
        'full_name' => 'Erinnya Lyonna',
        'email' => 'E-mail',
        'phone' => 'Essimu',
        'security_questions' => 'Ebibuuzo by\'Okwerinda',
        'security_questions_help' => 'Tereka ebibuuzo by\'okwerinda okusalawo password yo',
        'manage_security_questions' => 'Kola ku Ebibuuzo by\'Okwerinda',
        'save_changes' => 'Wanjula Ebikwata',
        'danger_zone' => 'Ekifo eky\'obuzibu',
        'rate_our_service' => 'Wandiisa Obwetaavu bwaffe',
        'select_complaint' => 'Londoola Ekikolwa Kyeddembe',
        'choose_complaint' => 'Londoola ekikolwa kyeddembe...',
        'no_resolved_complaints' => 'Tebalina bikolwa byeddembe byonna by\'okwandika.',
        'overall_rating' => 'Okwandika Kwonna',
        'tap_to_rate' => 'Koona ennyonyi okwandika',
        'response_time' => 'Obudde bw\'okuddamu',
        'response_excellent' => 'Kirungi nnyo (< olunaku 1)',
        'response_good' => 'Kirungi (olunaku 1-3)',
        'response_fair' => 'Kituufu (olunaku 3-7)',
        'response_poor' => 'Kibi (> olunaku 7)',
        'resolution_quality' => 'Obulungi bw\'Okuddamu',
        'quality_excellent' => 'Kirungi nnyo - Kweddembe bulungi',
        'quality_good' => 'Kirungi - Kweddembe ennyingi',
        'quality_fair' => 'Kituufu - Kweddembe kitono',
        'quality_poor' => 'Kibi - Tekiddembe',
        'would_recommend' => 'Opendekereza CampusVoice?',
        'feedback_label' => 'Ebikwata Ebirala (Ky\'okwagala)',
        'feedback_placeholder' => 'Tubuulire ebirala ku kifo kyaffe...',
        'submit_rating' => 'Waayo Okwandika',
        'share_complaint' => 'Yambagana Ekikolwa',
        'share_whatsapp' => 'WhatsApp',
        'share_twitter' => 'Twitter',
        'share_facebook' => 'Facebook',
        'share_copy' => 'Koppa Link',
        'share_email' => 'E-mail',
        'share_block' => 'Ziyiza',
        'nav_feed' => 'Ennyingo',
        'nav_post' => 'Waayo',
        'nav_alerts' => 'Amawulire',
        'nav_profile' => 'Enfaanana',
        'anonymous' => 'Mujulirwa',
        'user' => 'Omukozesa',
        'copy_link' => 'Link ekoppebwa',
        'user_blocked' => 'Omukozesa ayiziddwa',
        'profile_updated' => 'Enfaanana eyongedde',
        'login_required' => 'Erinnya liriisibwa',
        'logout_confirm' => 'Fuluma?',
        'block_confirm' => 'Ziyiza omukozesa ono?',
        'comment_placeholder' => 'Wandiika ebikwata...',
        'under_review' => 'Ekikolwa kyo kiri mu ntebe.',
        'comment_posted' => 'Ebikwata byaawereddwa',
        'liked' => 'ayagadde',
        'comments' => 'ebikwata',
        'share' => 'Yambagana',
        'now' => 'kaakano',
        'minutes' => 'ddak',
        'hours' => 'saa',
        'days' => 'nnaku',
        'language' => 'Lulimi',
        'english' => 'Lungereza',
        'swahili' => 'Kiswahili',
        'french' => 'Lufalansa',
        'luganda' => 'Luganda',
        'please_fill_all_questions' => 'Tewali ebibuuzo n\'ebigambo by\'okuddamu byonna',
        'select_different_questions' => 'Londoola ebibuuzo eby\'enjawulo ku buli kimu',
        'security_questions_saved' => 'Ebibuuzo by\'okwerinda byawandiikibwa bulungi',
        'error_loading_security' => 'Obuzibu mu kuleeta ebibuuzo by\'okwerinda',
        'error_saving_security' => 'Obuzibu mu kwandiika ebibuuzo by\'okwerinda',
        'saving' => 'Okwanjula',
        'save_security_questions' => 'Wanjula Ebibuuzo by\'Okwerinda',
        'submitting' => 'Okuwaayo',
        'thank_you_feedback' => 'Webale okutuukirira!',
        'connection_error' => 'Obuzibu bw\'okwetabula. Gezaako neera.'
    ]
];

// Get translations for current language
$lang = $translations[$current_language] ?? $translations['en'];

/* ---------- FETCH STUDENT FROM DB ---------- */
$stmt = $pdo->prepare("
    SELECT name, email, student_id, contact
    FROM students
    WHERE id = ?
");
$stmt->execute([$studentId]);
$dbStudent = $stmt->fetch(PDO::FETCH_ASSOC);

/* ---------- MERGE DB DATA WITH SESSION ---------- */
if ($dbStudent) {
    $_SESSION['student'] = array_merge($student, $dbStudent);
    $student = $_SESSION['student'];
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ---------- COMPLAINTS & NOTIFICATIONS ---------- */
$filter = $_GET['filter'] ?? 'all';
$complaints = [];

if ($filter === 'all') {
    $stmt = $pdo->query("SELECT * FROM complaints ORDER BY created_at DESC LIMIT 100");
} else {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE status = ? ORDER BY created_at DESC LIMIT 100");
    $stmt->execute([$filter]);
}
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$studentId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_status = 0");
$unread_count->execute([$studentId]);
$unread = $unread_count->fetchColumn();

/* ---------- STUDENT STATISTICS ---------- */
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_complaints,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints,
        SUM(CASE WHEN status = 'progress' THEN 1 ELSE 0 END) as progress_complaints
    FROM complaints 
    WHERE student_id = ?
");
$stats_stmt->execute([$studentId]);
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$rating_stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
    FROM complaint_ratings 
    WHERE student_id = ?
");
$rating_stmt->execute([$studentId]);
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);

$total_complaints = $user_stats['total_complaints'] ?? 0;
$resolved_complaints = $user_stats['resolved_complaints'] ?? 0;
$pending_complaints = $user_stats['pending_complaints'] ?? 0;
$progress_complaints = $user_stats['progress_complaints'] ?? 0;
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_ratings = $rating_data['total_ratings'] ?? 0;

$satisfaction_rate = $total_complaints > 0 ? round(($resolved_complaints / $total_complaints) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $lang['title']; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --secondary: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #3b82f6;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --text-light: #64748b;
      --border: #e2e8f0;
      --shadow: 0 1px 3px rgba(0,0,0,0.12);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
    }
    [data-theme="dark"] {
      --bg: #0f172a;
      --card: #1e293b;
      --text: #e2e8f0;
      --text-light: #94a3b8;
      --border: #334155;
      --shadow: 0 1px 3px rgba(0,0,0,0.3);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.4);
    }
    * {margin:0;padding:0;box-sizing:border-box;}
    body {
      font-family:'Inter',sans-serif;
      background:var(--bg);
      color:var(--text);
      min-height:100vh;
      padding-bottom:80px;
      transition: background 0.3s, color 0.3s;
    }

    /* Enhanced Header */
    header {
      background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);
      color:#fff;
      padding:14px 16px;
      position:sticky;
      top:0;
      z-index:100;
      box-shadow:var(--shadow-lg);
      border-radius:0 0 20px 20px;
    }
    .header-content {
      max-width:1200px;
      margin:0 auto;
      display:flex;
      justify-content:space-between;
      align-items:center;
    }
    .logo-section {
      display:flex;
      align-items:center;
      gap:10px;
    }
    .logo {
      height:40px;
      width:40px;
      border-radius:10px;
      background:#fff;
      padding:4px;
      box-shadow:0 4px 12px rgba(0,0,0,0.2);
      display:block !important;
    }
    .brand-text h1 {
      font-size:18px;
      font-weight:800;
      margin:0;
      line-height:1.2;
    }
    .brand-text p {
      font-size:10px;
      opacity:0.9;
      margin:0;
    }
    .header-actions {
      display:flex;
      gap:8px;
      align-items:center;
    }
    .header-btn {
      width:38px;
      height:38px;
      border-radius:10px;
      background:rgba(255,255,255,0.15);
      backdrop-filter:blur(10px);
      border:none;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      position:relative;
      transition:all 0.3s;
      font-size:18px;
    }
    .header-btn:hover {
      background:rgba(255,255,255,0.25);
      transform:translateY(-2px);
    }
    .notif-badge {
      position:absolute;
      top:-6px;
      right:-6px;
      background:var(--danger);
      color:#fff;
      font-size:10px;
      width:20px;
      height:20px;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
      border:2px solid var(--primary);
    }

    /* Language Dropdown */
    .language-selector {
      position:relative;
    }
    .language-dropdown {
      position:absolute;
      top:120%;
      right:0;
      background:var(--card);
      border-radius:10px;
      box-shadow:var(--shadow-lg);
      min-width:150px;
      display:none;
      z-index:1000;
      overflow:hidden;
    }
    .language-dropdown.active {
      display:block;
    }
    .lang-option {
      padding:12px 16px;
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:8px;
      color:var(--text);
      font-size:13px;
      transition:all 0.2s;
    }
    .lang-option:hover {
      background:var(--bg);
    }
    .lang-option.active {
      background:var(--primary-light);
      color:#fff;
    }

    /* Main Container */
    .main-wrapper {
      max-width:600px;
      margin:0 auto;
      padding:16px;
      display:grid;
      grid-template-columns:1fr;
      gap:20px;
    }
    @media (min-width:1025px) {
      .main-wrapper {
        max-width:1400px;
        grid-template-columns:1fr 350px;
        gap:24px;
      }
      .sidebar {
        display:block !important;
      }
    }

    /* Post Form Card */
    .post-card {
      background:var(--card);
      border-radius:16px;
      padding:16px;
      box-shadow:var(--shadow);
      margin-bottom:16px;
      border:1px solid var(--border);
    }
    .user-info {
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:14px;
    }
    .avatar {
      width:42px;
      height:42px;
      border-radius:50%;
      background:linear-gradient(135deg,var(--primary-light),var(--primary));
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:800;
      font-size:18px;
      flex-shrink:0;
      box-shadow:0 4px 12px rgba(30,58,138,0.3);
    }
    .user-details h3 {
      font-size:13px;
      font-weight:700;
      margin:0;
    }
    .user-details p {
      font-size:12px;
      color:var(--text-light);
      margin:2px 0 0;
    }
    .post-trigger {
      padding:12px 16px;
      border:2px solid var(--border);
      border-radius:12px;
      background:var(--bg);
      color:var(--text-light);
      cursor:pointer;
      font-size:14px;
      transition:all 0.3s;
      display:block;
      width:100%;
      text-align:left;
    }
    .post-trigger:hover {
      border-color:var(--primary);
      background:var(--card);
    }

    /* Feed Tabs */
    .feed-tabs {
      display:flex;
      gap:8px;
      margin-bottom:16px;
      overflow-x:auto;
      padding-bottom:4px;
    }
    .tab {
      padding:8px 14px;
      border-radius:10px;
      background:var(--card);
      border:2px solid var(--border);
      cursor:pointer;
      font-size:12px;
      font-weight:600;
      white-space:nowrap;
      transition:all 0.3s;
      color:var(--text);
    }
    .tab:hover {
      border-color:var(--primary-light);
    }
    .tab.active {
      background:linear-gradient(135deg,var(--primary),var(--primary-light));
      color:#fff;
      border-color:var(--primary);
      box-shadow:0 4px 12px rgba(30,58,138,0.3);
    }

    /* Complaint Cards */
    .complaint-card {
      background:var(--card);
      border-radius:14px;
      padding:16px;
      margin-bottom:12px;
      box-shadow:var(--shadow);
      border:1px solid var(--border);
      transition:all 0.3s;
      position:relative;
      overflow:hidden;
    }
    .complaint-card::before {
      content:'';
      position:absolute;
      top:0;
      left:0;
      width:4px;
      height:100%;
      background:linear-gradient(135deg,var(--primary),var(--primary-light));
    }
    .complaint-card:hover {
      box-shadow:var(--shadow-lg);
      transform:translateY(-2px);
    }
    .card-header {
      display:flex;
      justify-content:space-between;
      align-items:start;
      margin-bottom:12px;
    }
    .card-user {
      display:flex;
      gap:10px;
      flex:1;
    }
    .card-avatar {
      width:36px;
      height:36px;
      border-radius:10px;
      background:linear-gradient(135deg,#667eea,#764ba2);
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:700;
      font-size:14px;
      flex-shrink:0;
    }
    .card-meta h4 {
      font-size:13px;
      font-weight:700;
      margin:0 0 4px;
    }
    .card-meta p {
      font-size:11px;
      color:var(--text-light);
      display:flex;
      align-items:center;
      gap:6px;
    }
    .priority-badge {
      padding:6px 10px;
      border-radius:8px;
      font-size:10px;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:0.5px;
    }
    .priority-high{background:#fee2e2;color:#991b1b;}
    .priority-medium{background:#fef3c7;color:#92400e;}
    .priority-low{background:#e0e7ff;color:#3730a3;}
    .card-content {
      margin:12px 0;
      font-size:13px;
      line-height:1.6;
      color:var(--text);
    }
    .card-image {
      width:100%;
      border-radius:12px;
      margin:12px 0;
      max-height:300px;
      object-fit:cover;
      display:block;
    }
    .status-badge {
      display:inline-flex;
      align-items:center;
      gap:4px;
      padding:6px 10px;
      border-radius:8px;
      font-size:11px;
      font-weight:700;
      margin-top:8px;
    }
    .status-pending{background:#fef3c7;color:#92400e;}
    .status-progress{background:#e0e7ff;color:#3730a3;}
    .status-resolved{background:#dcfce7;color:#166534;}
    .card-footer {
      display:flex;
      gap:8px;
      padding-top:12px;
      border-top:1px solid var(--border);
      margin-top:12px;
    }
    .action-btn {
      flex:1;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      padding:8px;
      border-radius:10px;
      background:var(--bg);
      border:none;
      cursor:pointer;
      font-size:12px;
      font-weight:600;
      color:var(--text-light);
      transition:all 0.3s;
    }
    .action-btn:hover {
      background:var(--primary);
      color:#fff;
      transform:translateY(-1px);
    }
    .action-btn.active {
      background:var(--primary);
      color:#fff;
    }

    /* Sidebar */
    .sidebar {
      position:sticky;
      top:100px;
      height:fit-content;
      display:none;
    }
    .sidebar-card {
      background:var(--card);
      border-radius:14px;
      padding:16px;
      box-shadow:var(--shadow);
      border:1px solid var(--border);
      margin-bottom:16px;
    }
    .sidebar-title {
      font-size:13px;
      font-weight:700;
      margin:0 0 12px;
      color:var(--primary);
      display:flex;
      align-items:center;
      gap:6px;
    }
    .news-item {
      padding:10px;
      border-radius:10px;
      margin-bottom:8px;
      background:var(--bg);
      border-left:3px solid var(--primary);
      cursor:pointer;
      transition:all 0.3s;
    }
    .news-item:hover {
      background:var(--card);
      box-shadow:var(--shadow);
      transform:translateX(2px);
    }
    .news-item h5 {
      font-size:12px;
      font-weight:700;
      margin:0 0 4px;
    }
    .news-item p {
      font-size:10px;
      color:var(--text-light);
      display:flex;
      align-items:center;
      gap:4px;
    }

  /* Modal - FIXED VERSION */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  display: none; /* ← CRITICAL: Use display:none */
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal.active {
  display: flex;
}
    .modal-content {
      background:var(--card);
      border-radius:20px;
      max-width:500px;
      width:100%;
      max-height:90vh;
      overflow-y:auto;
      box-shadow:0 20px 60px rgba(0,0,0,0.3);
      animation:slideUp 0.3s ease;
    }
    @keyframes slideUp {
      from { opacity:0;transform:translateY(50px); }
      to { opacity:1;transform:translateY(0); }
    }
    .modal-header {
      padding:16px;
      border-bottom:1px solid var(--border);
      display:flex;
      justify-content:space-between;
      align-items:center;
      position:sticky;
      top:0;
      background:var(--card);
    }
    .modal-header h2 {
      font-size:16px;
      font-weight:700;
      color:var(--primary);
    }
    .close-btn {
      width:32px;
      height:32px;
      border-radius:10px;
      border:none;
      background:var(--bg);
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
      color:var(--text);
      transition:all 0.3s;
    }
    .close-btn:hover {
      background:var(--border);
    }
    .modal-body {
      padding:16px;
    }
    .form-group {
      margin-bottom:14px;
    }
    .form-label {
      font-size:12px;
      font-weight:700;
      color:var(--primary);
      margin-bottom:6px;
      display:flex;
      align-items:center;
      gap:4px;
    }
    input,select,textarea {
      width:100%;
      padding:11px;
      border:2px solid var(--border);
      border-radius:10px;
      font-size:13px;
      background:var(--bg);
      color:var(--text);
      font-family:inherit;
      transition:all 0.3s;
    }
    input:focus,select:focus,textarea:focus {
      outline:none;
      border-color:var(--primary);
      background:var(--card);
    }
    textarea {
      min-height:100px;
      resize:vertical;
    }
    .spinner {
        width:24px;
        height:24px;
        border:3px solid var(--border);
        border-top-color:var(--primary);
        border-radius:50%;
        animation:spin 1s linear infinite;
        margin:0 auto;
    }
    @keyframes spin {
        to { transform:rotate(360deg); }
    }
    .toast {
        position:fixed;
        bottom:90px;
        left:50%;
        transform:translateX(-50%);
        background:var(--card);
        color:var(--text);
        padding:12px 24px;
        border-radius:10px;
        box-shadow:var(--shadow-lg);
        z-index:10000;
        animation:slideUp 0.3s ease;
        border-left:4px solid var(--primary);
    }
    .toast.success { border-left-color:var(--secondary); }
    .toast.error { border-left-color:var(--danger); }
    .toast.warning { border-left-color:var(--warning); }
    .star-btn {
      background:none;
      border:none;
      font-size:36px;
      color:#cbd5e1;
      cursor:pointer;
      transition:all 0.2s;
      padding:4px;
    }
    .star-btn:hover {
      transform:scale(1.2);
    }
    .star-btn.active {
      color:#fbbf24;
    }
    .star-btn.active i {
      content:'\f586';
    }
    .security-question, .security-answer {
        width:100%;
        padding:10px;
        border:2px solid var(--border);
        border-radius:8px;
        font-size:13px;
        background:var(--card);
        color:var(--text);
        font-family:inherit;
        transition:all 0.3s;
    }
    .security-question:focus, .security-answer:focus {
        outline:none;
        border-color:var(--primary);
    }
    .security-answer {
        margin-top:6px;
    }
    .toggle-switch {
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:12px;
      background:var(--bg);
      border-radius:10px;
      margin-bottom:14px;
    }
    .toggle-label {
      display:flex;
      align-items:center;
      gap:8px;
      font-weight:600;
      font-size:13px;
    }
    .switch {
      position:relative;
      width:50px;
      height:28px;
    }
    .switch input {
      opacity:0;
      width:0;
      height:0;
    }
    .slider {
      position:absolute;
      cursor:pointer;
      top:0;
      left:0;
      right:0;
      bottom:0;
      background:#cbd5e1;
      transition:0.4s;
      border-radius:30px;
    }
    .slider:before {
      position:absolute;
      content:"";
      height:20px;
      width:20px;
      left:4px;
      bottom:4px;
      background:white;
      transition:0.4s;
      border-radius:50%;
    }
    input:checked + .slider {
      background:var(--primary);
    }
    input:checked + .slider:before {
      transform:translateX(22px);
    }
    .file-upload-zone {
      border:2px dashed var(--border);
      border-radius:10px;
      padding:20px;
      text-align:center;
      cursor:pointer;
      transition:all 0.3s;
      background:var(--bg);
    }
    .file-upload-zone:hover {
      border-color:var(--primary);
      background:var(--card);
    }
    .file-uploaded {
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px;
      background:var(--bg);
      border-radius:10px;
      margin-top:10px;
    }
    .btn-primary {
      background:linear-gradient(135deg,var(--primary),var(--primary-light));
      color:#fff;
      border:none;
      padding:12px 24px;
      border-radius:10px;
      font-weight:700;
      font-size:13px;
      cursor:pointer;
      transition:all 0.3s;
      box-shadow:0 4px 12px rgba(30,58,138,0.3);
    }
    .btn-primary:hover {
      transform:translateY(-2px);
      box-shadow:0 6px 20px rgba(30,58,138,0.4);
    }
    .btn-secondary {
      background:var(--bg);
      color:var(--text);
      border:2px solid var(--border);
      padding:12px 24px;
      border-radius:10px;
      font-weight:700;
      font-size:13px;
      cursor:pointer;
      transition:all 0.3s;
    }
    .btn-secondary:hover {
      border-color:var(--primary);
      color:var(--primary);
    }
    .priority-btn {
      padding:10px;
      border-radius:8px;
      border:2px solid var(--border);
      background:var(--bg);
      cursor:pointer;
      font-weight:600;
      font-size:12px;
      transition:all 0.3s;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:4px;
    }
    .priority-btn:hover {
      border-color:var(--primary);
      transform:translateY(-2px);
    }
    .priority-btn.active {
      background:var(--primary);
      color:#fff;
      border-color:var(--primary);
    }
    .share-menu {
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:10px;
      margin-bottom:12px;
    }
    .share-btn {
      padding:12px;
      border-radius:10px;
      border:2px solid var(--border);
      background:var(--bg);
      cursor:pointer;
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:6px;
      transition:all 0.3s;
      font-size:11px;
    }
    .share-btn:hover {
      border-color:var(--primary);
      transform:translateY(-3px);
      box-shadow:var(--shadow);
    }
    .share-btn i {
      font-size:20px;
    }
    .bottom-nav {
      position:fixed;
      bottom:0;
      left:0;
      right:0;
      background:var(--card);
      border-top:1px solid var(--border);
      display:flex;
      justify-content:space-around;
      padding:10px 0;
      box-shadow:0 -4px 12px rgba(0,0,0,0.08);
      z-index:100;
    }
    .nav-item {
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:4px;
      font-size:10px;
      color:var(--text-light);
      cursor:pointer;
      padding:8px 12px;
      border-radius:10px;
      transition:all 0.3s;
    }
    .nav-item i {
      font-size:20px;
    }
    .nav-item.active {
      color:var(--primary);
      background:rgba(59,130,246,0.1);
    }
    .notification-item {
      padding:12px;
      background:var(--bg);
      border-radius:10px;
      margin-bottom:8px;
      border-left:3px solid var(--primary);
      cursor:pointer;
      transition:all 0.3s;
    }
    .notification-item:hover {
      background:var(--card);
      box-shadow:var(--shadow);
    }
    .notif-title {
      font-size:13px;
      font-weight:700;
      margin-bottom:4px;
    }
    .notif-desc {
      font-size:12px;
      color:var(--text-light);
    }
    .notif-time {
      font-size:10px;
      color:var(--text-light);
      margin-top:4px;
    }
    .profile-section {
      text-align:center;
      padding:20px;
      border-bottom:1px solid var(--border);
    }
    .profile-avatar {
      width:80px;
      height:80px;
      border-radius:50%;
      background:linear-gradient(135deg,var(--primary-light),var(--primary));
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:800;
      font-size:32px;
      margin:0 auto 12px;
      box-shadow:0 4px 12px rgba(30,58,138,0.3);
    }
    .profile-section h2 {
      font-size:18px;
      font-weight:700;
      margin:0 0 4px;
    }
    .profile-section p {
      font-size:12px;
      color:var(--text-light);
      margin:0;
    }
    .profile-stats {
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:12px;
      margin-top:16px;
    }
    .stat-item {
      background:var(--bg);
      padding:12px;
      border-radius:10px;
    }
    .stat-number {
      font-size:16px;
      font-weight:800;
      color:var(--primary);
    }
    .stat-label {
      font-size:11px;
      color:var(--text-light);
    }
  </style>
</head>
<body>

  <!-- Enhanced Header -->
  <header>
    <div class="header-content">
      <div class="logo-section">
        <img src="assets/bugemalogo.jpg" class="logo" alt="Bugema University" onerror="this.src='https://via.placeholder.com/40/1E3A8A/white?text=BU'">
        <div class="brand-text">
          <h1><?php echo $lang['brand_name']; ?></h1>
          <p><?php echo $lang['brand_subtitle']; ?></p>
        </div>
      </div>
      <div class="header-actions">
        <!-- Language Selector -->
        <div class="language-selector">
          <button class="header-btn" onclick="toggleLanguageDropdown()" title="<?php echo $lang['language']; ?>">
            <i class="bi bi-globe"></i>
          </button>
          <div class="language-dropdown" id="languageDropdown">
            <div class="lang-option <?php echo $current_language === 'en' ? 'active' : ''; ?>" onclick="setLanguage('en')">
              <i class="bi bi-check-lg"></i> <?php echo $lang['english']; ?>
            </div>
            <div class="lang-option <?php echo $current_language === 'sw' ? 'active' : ''; ?>" onclick="setLanguage('sw')">
              <i class="bi bi-check-lg"></i> <?php echo $lang['swahili']; ?>
            </div>
            <div class="lang-option <?php echo $current_language === 'fr' ? 'active' : ''; ?>" onclick="setLanguage('fr')">
              <i class="bi bi-check-lg"></i> <?php echo $lang['french']; ?>
            </div>
            <div class="lang-option <?php echo $current_language === 'lg' ? 'active' : ''; ?>" onclick="setLanguage('lg')">
              <i class="bi bi-check-lg"></i> <?php echo $lang['luganda']; ?>
            </div>
          </div>
        </div>
        
        <button class="header-btn" onclick="openNotifications()" title="<?php echo $lang['notifications']; ?>">
          <i class="bi bi-bell-fill"></i>
          <span id="notifCount" class="notif-badge" style="display:none">0</span>
        </button>
        <button class="header-btn" onclick="toggleTheme()" title="<?php echo $lang['toggle_theme']; ?>">
          <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
        <button class="header-btn" onclick="openProfile()" title="<?php echo $lang['profile']; ?>">
          <i class="bi bi-person-fill"></i>
        </button>
        <button class="header-btn" onclick="logout()" title="<?php echo $lang['logout']; ?>">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Main Wrapper -->
  <div class="main-wrapper">
    <!-- Main Feed -->
    <div class="main-feed">
      <!-- Post Card -->
      <div class="post-card">
        <div class="user-info">
          <div class="avatar" id="userAvatar">U</div>
          <div class="user-details">
            <h3 id="userName"><?php echo htmlspecialchars($student['name'] ?? 'Student User'); ?></h3>
            <p id="userEmail"><?php echo htmlspecialchars($student['email'] ?? 'student@bugema.ac.ug'); ?></p>
          </div>
        </div>
        <div class="post-trigger" onclick="openPostModal()">
          <i class="bi bi-pencil-square"></i> <?php echo $lang['greeting']; ?>
        </div>
      </div>

      <!-- Feed Tabs -->
      <div class="feed-tabs">
        <div class="tab active" onclick="filterFeed('all',this)"><?php echo $lang['tab_all']; ?></div>
        <div class="tab" onclick="filterFeed('pending',this)"><?php echo $lang['tab_pending']; ?></div>
        <div class="tab" onclick="filterFeed('progress',this)"><?php echo $lang['tab_progress']; ?></div>
        <div class="tab" onclick="filterFeed('resolved',this)"><?php echo $lang['tab_resolved']; ?></div>
      </div>

      <!-- Complaints Feed -->
      <div id="complaints-feed">
        <div style="text-align:center;padding:40px 20px;color:var(--text-light)">
          <i class="bi bi-inbox" style="font-size:48px;opacity:0.3"></i>
          <p style="margin-top:12px;font-size:13px"><?php echo $lang['loading_complaints']; ?></p>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-newspaper"></i> <?php echo $lang['current_feed']; ?>
        </div>
        <div id="newsFeed">
          <p style="font-size:12px;color:var(--text-light)"><?php echo $lang['loading']; ?></p>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-fire"></i> <?php echo $lang['trending_issues']; ?>
        </div>
        <div id="trendingIssues">
          <p style="font-size:12px;color:var(--text-light)"><?php echo $lang['loading']; ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Post Modal -->
  <div class="modal" id="postModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-megaphone-fill"></i> <?php echo $lang['lodge_complaint']; ?></h2>
        <button class="close-btn" onclick="closePostModal()">×</button>
      </div>
      <div class="modal-body">
        <form id="complaintForm" onsubmit="submitComplaint(event)">
          
          <!-- Anonymous Toggle -->
          <div class="toggle-switch">
            <div class="toggle-label">
              <i class="bi bi-incognito"></i>
              <span><?php echo $lang['post_anonymously']; ?></span>
            </div>
            <label class="switch">
              <input type="checkbox" id="anonymous">
              <span class="slider"></span>
            </label>
          </div>

          <!-- Title -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-text-left"></i> <?php echo $lang['title_label']; ?>
            </label>
            <input id="title" placeholder="<?php echo $lang['title_placeholder']; ?>" required>
          </div>

          <!-- Category -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-tag-fill"></i> <?php echo $lang['category_label']; ?>
            </label>
            <select id="category" required>
              <option value=""><?php echo $lang['category_placeholder']; ?></option>
              <option value="infrastructure"><?php echo $lang['category_infrastructure']; ?></option>
              <option value="it"><?php echo $lang['category_it']; ?></option>
              <option value="accommodation"><?php echo $lang['category_accommodation']; ?></option>
              <option value="food"><?php echo $lang['category_food']; ?></option>
              <option value="academic"><?php echo $lang['category_academic']; ?></option>
              <option value="security"><?php echo $lang['category_security']; ?></option>
              <option value="other"><?php echo $lang['category_other']; ?></option>
            </select>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-chat-left-text-fill"></i> <?php echo $lang['description_label']; ?>
            </label>
            <textarea id="description" placeholder="<?php echo $lang['description_placeholder']; ?>" required></textarea>
          </div>

          <!-- Location -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-geo-alt-fill"></i> <?php echo $lang['location_label']; ?>
            </label>
            <input id="location" placeholder="<?php echo $lang['location_placeholder']; ?>">
          </div>

          <!-- Priority -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $lang['priority_label']; ?>
            </label>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
              <button type="button" class="priority-btn" data-priority="low" onclick="selectPriority(this,'low')">
                <i class="bi bi-arrow-down"></i> <?php echo $lang['priority_low']; ?>
              </button>
              <button type="button" class="priority-btn active" data-priority="medium" onclick="selectPriority(this,'medium')">
                <i class="bi bi-dash"></i> <?php echo $lang['priority_medium']; ?>
              </button>
              <button type="button" class="priority-btn" data-priority="high" onclick="selectPriority(this,'high')">
                <i class="bi bi-arrow-up"></i> <?php echo $lang['priority_high']; ?>
              </button>
            </div>
            <input type="hidden" id="priority" value="medium">
          </div>

          <!-- File Upload -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-paperclip"></i> <?php echo $lang['attach_evidence']; ?>
            </label>
            <div class="file-upload-zone" onclick="document.getElementById('fileInput').click()">
              <i class="bi bi-cloud-arrow-up" style="font-size:32px;color:var(--primary);opacity:0.5"></i>
              <p style="margin-top:8px;font-size:12px;color:var(--text-light)"><?php echo $lang['upload_text']; ?></p>
              <p style="font-size:11px;color:var(--text-light);margin-top:2px"><?php echo $lang['upload_max']; ?></p>
            </div>
            <input type="file" id="fileInput" accept="image/*,.pdf" style="display:none">
            <div id="filePreview"></div>
          </div>

          <!-- Submit Buttons -->
          <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="btn-secondary" style="flex:1" onclick="closePostModal()"><?php echo $lang['cancel']; ?></button>
            <button type="submit" class="btn-primary" style="flex:2" id="submitBtn">
              <i class="bi bi-send-fill"></i> <?php echo $lang['submit']; ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Notifications Modal -->
  <div class="modal" id="notificationsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-bell-fill"></i> <?php echo $lang['notifications']; ?></h2>
        <button class="close-btn" onclick="closeNotifications()">×</button>
      </div>
      <div class="modal-body">
        <div id="notificationsList" style="max-height:500px;overflow-y:auto;">
          <div style="text-align:center;padding:40px 20px;color:var(--text-light)">
            <i class="bi bi-inbox" style="font-size:48px;opacity:0.3"></i>
            <p style="margin-top:12px;font-size:13px">No notifications yet</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Modal -->
  <div class="modal" id="profileModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-person-fill"></i> <?php echo $lang['my_profile']; ?></h2>
        <button class="close-btn" onclick="closeProfile()">×</button>
      </div>
      <div class="modal-body">
        <!-- Profile Section -->
        <div class="profile-section">
          <div class="profile-avatar" id="profileAvatar">U</div>
          <h2 id="profileName"><?php echo htmlspecialchars($student['name'] ?? 'Student User'); ?></h2>
          <p id="profileEmail"><?php echo htmlspecialchars($student['email'] ?? 'student@bugema.ac.ug'); ?></p>
          <p id="profileId" style="font-size:11px;margin-top:4px;color:var(--primary);font-weight:600"><?php echo htmlspecialchars($student['student_id'] ?? '—'); ?></p>
          
          <div class="profile-stats">
            <div class="stat-item">
              <div class="stat-number" id="profileComplaints"><?php echo $total_complaints; ?></div>
              <div class="stat-label"><?php echo $lang['complaints']; ?></div>
            </div>
            <div class="stat-item">
              <div class="stat-number" id="profileResolved"><?php echo $resolved_complaints; ?></div>
              <div class="stat-label"><?php echo $lang['resolved']; ?></div>
            </div>
            <div class="stat-item">
              <div class="stat-number" id="profileSatisfaction"><?php echo $satisfaction_rate; ?>%</div>
              <div class="stat-label"><?php echo $lang['success_rate']; ?></div>
            </div>
          </div>

          <!-- Additional Stats -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;">
            <div style="background:var(--bg);padding:10px;border-radius:8px;text-align:center;">
              <div style="font-size:14px;font-weight:700;color:var(--warning);"><?php echo $pending_complaints; ?></div>
              <div style="font-size:10px;color:var(--text-light);"><?php echo $lang['pending']; ?></div>
            </div>
            <div style="background:var(--bg);padding:10px;border-radius:8px;text-align:center;">
              <div style="font-size:14px;font-weight:700;color:var(--info);"><?php echo $progress_complaints; ?></div>
              <div style="font-size:10px;color:var(--text-light);"><?php echo $lang['in_progress']; ?></div>
            </div>
          </div>
        </div>

        <!-- Rate Service Button -->
        <div style="padding:16px;border-bottom:1px solid var(--border);">
          <button class="btn-primary" style="width:100%;" onclick="openRatingModal()">
            <i class="bi bi-star-fill"></i> <?php echo $lang['rate_service']; ?>
          </button>
          <p style="font-size:11px;color:var(--text-light);text-align:center;margin-top:8px;">
            <?php echo $lang['rate_help']; ?>
          </p>
        </div>

        <!-- Account Settings -->
        <div style="padding:16px;">
          <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:12px;">
            <i class="bi bi-gear-fill"></i> <?php echo $lang['account_settings']; ?>
          </h3>

          <div class="form-group">
            <label class="form-label"><?php echo $lang['full_name']; ?></label>
            <input id="editName" type="text" placeholder="<?php echo $lang['full_name']; ?>">
          </div>

          <div class="form-group">
            <label class="form-label"><?php echo $lang['email']; ?></label>
            <input id="editEmail" type="email" placeholder="your@email.com" disabled style="opacity:0.6;">
          </div>

          <div class="form-group">
            <label class="form-label"><?php echo $lang['phone']; ?></label>
            <input id="editPhone" type="tel" placeholder="+256...">
          </div>

          <!-- Security Questions Section -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-shield-lock-fill"></i> <?php echo $lang['security_questions']; ?>
            </label>
            <div style="background:var(--bg);padding:12px;border-radius:10px;margin-top:8px;">
              <p style="font-size:11px;color:var(--text-light);margin-bottom:10px;">
                <?php echo $lang['security_questions_help']; ?>
              </p>
              <button type="button" class="btn-secondary" style="width:100%;" onclick="openSecurityQuestionsModal()">
                <i class="bi bi-question-circle-fill"></i> <?php echo $lang['manage_security_questions']; ?>
              </button>
            </div>
          </div>

          <button class="btn-primary" style="width:100%;" onclick="saveProfile()">
            <i class="bi bi-check-lg"></i> <?php echo $lang['save_changes']; ?>
          </button>
        </div>

        <!-- Danger Zone -->
        <div style="padding:16px;border-top:1px solid var(--border);">
          <h3 style="font-size:14px;font-weight:700;color:var(--danger);margin-bottom:12px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $lang['danger_zone']; ?>
          </h3>

          <button class="btn-secondary" style="width:100%;color:var(--danger);border-color:var(--danger);" onclick="logout()">
            <i class="bi bi-box-arrow-right"></i> <?php echo $lang['logout']; ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Security Questions Modal -->
  <div class="modal" id="securityQuestionsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-shield-lock-fill"></i> <?php echo $lang['security_questions']; ?></h2>
        <button class="close-btn" onclick="closeSecurityQuestionsModal()">×</button>
      </div>
      <div class="modal-body">
        <form id="securityQuestionsForm" onsubmit="saveSecurityQuestions(event)">
          <p style="font-size:12px;color:var(--text-light);margin-bottom:16px;text-align:center;">
            Set up 5 security questions for password recovery
          </p>

          <?php for($i = 1; $i <= 5; $i++): ?>
          <div class="form-group" style="margin-bottom:16px;padding:12px;background:var(--bg);border-radius:10px;">
            <label class="form-label" style="font-size:11px;">Question <?php echo $i; ?></label>
            <select id="security_question_<?php echo $i; ?>" class="security-question" required style="margin-bottom:8px;">
              <option value="">Select a question</option>
              <option value="What was your childhood nickname?">What was your childhood nickname?</option>
              <option value="What is the name of your favorite childhood friend?">What is the name of your favorite childhood friend?</option>
              <option value="What street did you live on in third grade?">What street did you live on in third grade?</option>
              <option value="What is the middle name of your youngest child?">What is the middle name of your youngest child?</option>
              <option value="What is your oldest sibling's middle name?">What is your oldest sibling's middle name?</option>
              <option value="What school did you attend for sixth grade?">What school did you attend for sixth grade?</option>
              <option value="What was your childhood phone number?">What was your childhood phone number?</option>
              <option value="What is your paternal grandmother's maiden name?">What is your paternal grandmother's maiden name?</option>
              <option value="In what city or town did your mother and father meet?">In what city or town did your mother and father meet?</option>
              <option value="What was the name of your first pet?">What was the name of your first pet?</option>
              <option value="What was the first car you owned?">What was the first car you owned?</option>
              <option value="What is your favorite movie?">What is your favorite movie?</option>
              <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
              <option value="What is your favorite book?">What is your favorite book?</option>
              <option value="What is the name of the town where you were born?">What is the name of the town where you were born?</option>
              <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
              <option value="What is your favorite food?">What is your favorite food?</option>
              <option value="What is your favorite sports team?">What is your favorite sports team?</option>
              <option value="What is the name of your favorite teacher?">What is the name of your favorite teacher?</option>
            </select>
            <input type="text" id="security_answer_<?php echo $i; ?>" class="security-answer" 
                   placeholder="Your answer" required style="font-size:12px;">
          </div>
          <?php endfor; ?>

          <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="btn-secondary" style="flex:1;" onclick="closeSecurityQuestionsModal()">
              <?php echo $lang['cancel']; ?>
            </button>
            <button type="submit" class="btn-primary" style="flex:2;" id="saveSecurityQuestionsBtn">
              <i class="bi bi-check-lg"></i> <?php echo $lang['save_changes']; ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Rating/Feedback Modal -->
  <div class="modal" id="ratingModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-star-fill"></i> <?php echo $lang['rate_our_service']; ?></h2>
        <button class="close-btn" onclick="closeRatingModal()">×</button>
      </div>
      <div class="modal-body">
        <form id="ratingForm" onsubmit="submitRating(event)">
          
          <!-- Select Complaint -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-list-check"></i> <?php echo $lang['select_complaint']; ?>
            </label>
            <select id="rating_complaint_id" required>
              <option value=""><?php echo $lang['choose_complaint']; ?></option>
              <?php
              // Get user's resolved complaints that haven't been rated
              $resolved_stmt = $pdo->prepare("
                  SELECT c.id, c.title, c.created_at 
                  FROM complaints c
                  LEFT JOIN complaint_ratings cr ON c.id = cr.complaint_id AND cr.student_id = ?
                  WHERE c.student_id = ? AND c.status = 'resolved' AND cr.id IS NULL
                  ORDER BY c.created_at DESC
              ");
              $resolved_stmt->execute([$studentId, $studentId]);
              $resolved_complaints = $resolved_stmt->fetchAll(PDO::FETCH_ASSOC);
              
              foreach ($resolved_complaints as $rc) {
                  echo "<option value='{$rc['id']}'>" . htmlspecialchars($rc['title']) . " (" . date('M d', strtotime($rc['created_at'])) . ")</option>";
              }
              ?>
            </select>
            <?php if (empty($resolved_complaints)): ?>
            <p style="font-size:11px;color:var(--text-light);margin-top:6px;">
              <?php echo $lang['no_resolved_complaints']; ?>
            </p>
            <?php endif; ?>
          </div>

          <!-- Star Rating -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-star-fill"></i> <?php echo $lang['overall_rating']; ?>
            </label>
            <div style="display:flex;gap:8px;justify-content:center;margin:12px 0;">
              <button type="button" class="star-btn" data-rating="1" onclick="selectRating(1)">
                <i class="bi bi-star"></i>
              </button>
              <button type="button" class="star-btn" data-rating="2" onclick="selectRating(2)">
                <i class="bi bi-star"></i>
              </button>
              <button type="button" class="star-btn" data-rating="3" onclick="selectRating(3)">
                <i class="bi bi-star"></i>
              </button>
              <button type="button" class="star-btn" data-rating="4" onclick="selectRating(4)">
                <i class="bi bi-star"></i>
              </button>
              <button type="button" class="star-btn" data-rating="5" onclick="selectRating(5)">
                <i class="bi bi-star"></i>
              </button>
            </div>
            <input type="hidden" id="rating_value" required>
            <p id="ratingText" style="text-align:center;font-size:12px;color:var(--text-light);margin-top:8px;">
              <?php echo $lang['tap_to_rate']; ?>
            </p>
          </div>

          <!-- Response Time -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-clock-fill"></i> <?php echo $lang['response_time']; ?>
            </label>
            <select id="rating_response_time" required>
              <option value="">Select...</option>
              <option value="excellent"><?php echo $lang['response_excellent']; ?></option>
              <option value="good"><?php echo $lang['response_good']; ?></option>
              <option value="fair"><?php echo $lang['response_fair']; ?></option>
              <option value="poor"><?php echo $lang['response_poor']; ?></option>
            </select>
          </div>

          <!-- Resolution Quality -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-award-fill"></i> <?php echo $lang['resolution_quality']; ?>
            </label>
            <select id="rating_resolution_quality" required>
              <option value="">Select...</option>
              <option value="excellent"><?php echo $lang['quality_excellent']; ?></option>
              <option value="good"><?php echo $lang['quality_good']; ?></option>
              <option value="fair"><?php echo $lang['quality_fair']; ?></option>
              <option value="poor"><?php echo $lang['quality_poor']; ?></option>
            </select>
          </div>

          <!-- Would Recommend -->
          <div class="form-group">
            <div class="toggle-switch" style="margin:0;">
              <div class="toggle-label">
                <i class="bi bi-hand-thumbs-up-fill"></i>
                <span><?php echo $lang['would_recommend']; ?></span>
              </div>
              <label class="switch">
                <input type="checkbox" id="rating_would_recommend">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <!-- Feedback -->
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-chat-square-text-fill"></i> <?php echo $lang['feedback_label']; ?>
            </label>
            <textarea id="rating_feedback" placeholder="<?php echo $lang['feedback_placeholder']; ?>" rows="4"></textarea>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-primary" style="width:100%;" id="ratingSubmitBtn">
            <i class="bi bi-send-fill"></i> <?php echo $lang['submit_rating']; ?>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Share Modal -->
  <div class="modal" id="shareModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="bi bi-share-fill"></i> <?php echo $lang['share_complaint']; ?></h2>
        <button class="close-btn" onclick="closeShareModal()">×</button>
      </div>
      <div class="modal-body">
        <div class="share-menu">
          <div class="share-btn" onclick="shareVia('whatsapp')">
            <i class="bi bi-whatsapp" style="color:#25D366"></i>
            <span><?php echo $lang['share_whatsapp']; ?></span>
          </div>
          <div class="share-btn" onclick="shareVia('twitter')">
            <i class="bi bi-twitter" style="color:#1DA1F2"></i>
            <span><?php echo $lang['share_twitter']; ?></span>
          </div>
          <div class="share-btn" onclick="shareVia('facebook')">
            <i class="bi bi-facebook" style="color:#1877F2"></i>
            <span><?php echo $lang['share_facebook']; ?></span>
          </div>
          <div class="share-btn" onclick="shareVia('copy')">
            <i class="bi bi-link-45deg" style="color:var(--primary)"></i>
            <span><?php echo $lang['share_copy']; ?></span>
          </div>
          <div class="share-btn" onclick="shareVia('email')">
            <i class="bi bi-envelope-fill" style="color:var(--danger)"></i>
            <span><?php echo $lang['share_email']; ?></span>
          </div>
          <div class="share-btn" onclick="blockUser()">
            <i class="bi bi-ban" style="color:var(--danger)"></i>
            <span><?php echo $lang['share_block']; ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Nav -->
  <div class="bottom-nav">
    <div class="nav-item active" onclick="scrollToFeed()">
      <i class="bi bi-house-door-fill"></i>
      <span><?php echo $lang['nav_feed']; ?></span>
    </div>
    <div class="nav-item" onclick="openPostModal()">
      <i class="bi bi-plus-circle-fill"></i>
      <span><?php echo $lang['nav_post']; ?></span>
    </div>
    <div class="nav-item" onclick="openNotifications()">
      <i class="bi bi-bell-fill"></i>
      <span><?php echo $lang['nav_alerts']; ?></span>
    </div>
    <div class="nav-item" onclick="openProfile()">
      <i class="bi bi-person-fill"></i>
      <span><?php echo $lang['nav_profile']; ?></span>
    </div>
  </div>

  <script>
/* ---------- LANGUAGE SETUP ---------- */
const LANGUAGE = <?php echo json_encode($lang); ?>;
const CURRENT_LANG = '<?php echo $current_language; ?>';

// Language switching function
function setLanguage(lang) {
    window.location.href = '?lang=' + lang;
}

function toggleLanguageDropdown() {
    const dropdown = document.getElementById('languageDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('languageDropdown');
    const button = document.querySelector('.language-selector .header-btn');
    if (!dropdown.contains(e.target) && !button.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});

/* ---------- USER DATA FROM PHP (INJECTED) ---------- */
const USER = <?php
    echo json_encode([
        'name'       => $dbStudent['name'] ?? $student['name'] ?? 'Student User',
        'email'      => $dbStudent['email'] ?? $student['email'] ?? 'student@bugema.ac.ug',
        'student_id' => $dbStudent['student_id'] ?? '',
        'contact'    => $dbStudent['contact'] ?? '',
        'unread'     => $unread ?? 0
    ]);
?>;

// DARK MODE
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
document.getElementById('themeIcon').className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';

function toggleTheme() {
    const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    document.getElementById('themeIcon').className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
}

let currentFilter = 'all';
let selectedPriority = 'medium';
let currentShareData = {};
let complaints = [];

// === MODAL FUNCTIONS ===
function openPostModal() {
    console.log('Opening post modal');
    document.getElementById('postModal').classList.add('active');
}
function closePostModal() {
    document.getElementById('postModal').classList.remove('active');
    document.getElementById('complaintForm').reset();
    document.getElementById('filePreview').innerHTML = '';
    selectedPriority = 'medium';
    document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('active'));
    const mediumBtn = document.querySelector('.priority-btn[data-priority="medium"]');
    if (mediumBtn) mediumBtn.classList.add('active');
}
function openNotifications() {
    console.log('Opening notifications');
    document.getElementById('notificationsModal').classList.add('active');
    renderNotifications();
}
function closeNotifications() {
    document.getElementById('notificationsModal').classList.remove('active');
}
function openProfile() {
    console.log('Opening profile');
    document.getElementById('profileModal').classList.add('active');
}
function closeProfile() {
    document.getElementById('profileModal').classList.remove('active');
}
function openSecurityQuestionsModal() {
    closeProfile();
    document.getElementById('securityQuestionsModal').classList.add('active');
    loadExistingSecurityQuestions();
}
function closeSecurityQuestionsModal() {
    document.getElementById('securityQuestionsModal').classList.remove('active');
}
function openRatingModal() {
    closeProfile();
    document.getElementById('ratingModal').classList.add('active');
}
function closeRatingModal() {
    document.getElementById('ratingModal').classList.remove('active');
    document.getElementById('ratingForm').reset();
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.querySelector('i').className = 'bi bi-star';
    });
    document.getElementById('ratingText').textContent = LANGUAGE.tap_to_rate;
}
function openShareModal(id, title, desc) {
    currentShareData = { id, title, desc };
    document.getElementById('shareModal').classList.add('active');
}
function closeShareModal() {
    document.getElementById('shareModal').classList.remove('active');
}

// Initialize Page with REAL DATA
function initPage() {
    console.log('Initializing page...');
    
    // === USER INFO ===
    document.getElementById('userName').textContent = USER.name;
    document.getElementById('userEmail').textContent = USER.email;
    document.getElementById('profileName').textContent = USER.name;
    document.getElementById('profileEmail').textContent = USER.email;
    document.getElementById('profileId').textContent = USER.student_id || '—';

    // Edit fields
    document.getElementById('editName').value = USER.name;
    document.getElementById('editEmail').value = USER.email;
    document.getElementById('editPhone').value = USER.contact || '';

    // === AVATAR (photo or initial) ===
    const setAvatar = (el) => {
        if (USER.photo && USER.photo.trim() !== '') {
            el.innerHTML = `<img src="uploads/${USER.photo}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
        } else {
            el.textContent = USER.name.charAt(0).toUpperCase();
        }
    };
    setAvatar(document.getElementById('userAvatar'));
    setAvatar(document.getElementById('profileAvatar'));

    // === NOTIFICATION BADGE ===
    const badge = document.getElementById('notifCount');
    if (USER.unread > 0) {
        badge.textContent = USER.unread > 99 ? '99+' : USER.unread;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }

    // === LOAD FEED & UI ===
    loadFeed();
    loadNews();
    checkNotifications();
}

// === FILE UPLOAD ===
document.getElementById('fileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('filePreview');
    if (file) {
        preview.innerHTML = `
            <div class="file-uploaded">
                <i class="bi bi-file-earmark-check-fill" style="font-size:20px;color:#10b981"></i>
                <div style="flex:1;text-align:left;">
                    <div style="font-weight:600;font-size:12px">${file.name}</div>
                    <div style="font-size:11px;color:#64748b">${(file.size / 1024).toFixed(1)} KB</div>
                </div>
                <button type="button" onclick="clearFile()" style="background:none;border:none;cursor:pointer;color:#ef4444">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        `;
    }
});
function clearFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').innerHTML = '';
}

// === PRIORITY ===
function selectPriority(btn, level) {
    selectedPriority = level;
    document.getElementById('priority').value = level;
    document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// === COMPLAINT SUBMISSION ===
async function submitComplaint(e) {
    e.preventDefault();
    
    const btn = document.getElementById('submitBtn');
    const originalText = btn.innerHTML;
    
    try {
        // Collect form data
        const formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('category', document.getElementById('category').value);
        formData.append('location', document.getElementById('location').value || '');
        formData.append('priority', selectedPriority);
        formData.append('anonymous', document.getElementById('anonymous').checked ? '1' : '0');
        
        // Add file if selected
        const fileInput = document.getElementById('fileInput');
        if (fileInput.files[0]) {
            formData.append('file', fileInput.files[0]);
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + LANGUAGE.submitting + '...';
        
        // Submit to API
        const response = await fetch('api/submit_complaint.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ ' + (data.message || 'Complaint submitted successfully!'), 'success');
            closePostModal();
            // Reload complaints feed
            setTimeout(() => {
                loadFeed();
                loadNews();
            }, 500);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Submission error:', error);
        showToast('❌ ' + LANGUAGE.connection_error, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> ' + LANGUAGE.submit;
    }
}

// === FILTER FEED ===
function filterFeed(status, el) {
    currentFilter = status;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    loadFeed();
}

// === LOAD FEED (from DB) ===
async function loadFeed() {
    const container = document.getElementById('complaints-feed');
    container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-light)">
        <div class="spinner" style="margin:20px auto"></div>
        <p style="margin-top:12px;font-size:13px">${LANGUAGE.loading_complaints}</p>
    </div>`;

    try {
        console.log('Loading feed with filter:', currentFilter);
        const response = await fetch(`api/get_complaints.php?filter=${currentFilter}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load complaints');
        }

        complaints = data.data?.complaints || [];
        
        if (complaints.length === 0) {
            container.innerHTML = `
                <div style="text-align:center;padding:40px 20px;color:var(--text-light)">
                    <i class="bi bi-inbox" style="font-size:48px;opacity:0.3"></i>
                    <p style="margin-top:12px;font-size:13px">${LANGUAGE.no_complaints}</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        complaints.forEach(c => {
            const statusText = c.status === 'pending' ? LANGUAGE.tab_pending : 
                             c.status === 'progress' ? LANGUAGE.tab_progress : LANGUAGE.tab_resolved;
            const statusClass = c.status === 'pending' ? 'status-pending' : 
                              c.status === 'progress' ? 'status-progress' : 'status-resolved';
            
            const nameDisplay = (c.anonymous == '1' || c.anonymous == 1) ? LANGUAGE.anonymous : (c.student_name || LANGUAGE.user);
            const avatarLetter = nameDisplay.charAt(0).toUpperCase();
            const imageHtml = c.image ? `<img src="${c.image}" class="card-image" alt="complaint image">` : '';

            const card = document.createElement('div');
            card.className = 'complaint-card';
            card.innerHTML = `
                <div class="card-header">
                    <div class="card-user">
                        <div class="card-avatar">${avatarLetter}</div>
                        <div class="card-meta">
                            <h4>${escapeHtml(c.title)}</h4>
                            <p>
                                <span>${nameDisplay}</span>
                                <span>•</span>
                                <span>${c.created_at_formatted || timeAgo(c.created_at)}</span>
                                ${c.location ? `<span>•</span><span><i class="bi bi-geo-alt-fill"></i> ${escapeHtml(c.location)}</span>` : ''}
                            </p>
                        </div>
                    </div>
                    <div class="priority-badge priority-${c.priority}">${c.priority.toUpperCase()}</div>
                </div>
                <div class="card-content">${escapeHtml(c.description)}</div>
                ${imageHtml}
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px">
                    <span style="font-size:11px;color:var(--text-light);display:flex;align-items:center;gap:4px">
                        <i class="bi bi-tag-fill"></i> ${escapeHtml(c.category)}
                    </span>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="card-footer">
                    <button class="action-btn ${c.user_liked ? 'active' : ''}" onclick="toggleLike(${c.id}, this)">
                        <i class="bi ${c.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                        <span>${c.likes || 0}</span>
                    </button>
                    <button class="action-btn" onclick="toggleComments(${c.id})">
                        <i class="bi bi-chat-dots"></i>
                        <span>${c.comment_count || 0}</span>
                    </button>
                    <button class="action-btn" onclick="openShareModal(${c.id}, '${escapeHtml(c.title)}', '${escapeHtml(c.description)}')">
                        <i class="bi bi-share"></i>
                        <span>${LANGUAGE.share}</span>
                    </button>
                </div>
                <div id="comments-panel-${c.id}" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)"></div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Error loading feed:', error);
        container.innerHTML = `
            <div style="text-align:center;padding:40px 20px;color:var(--danger)">
                <i class="bi bi-exclamation-triangle" style="font-size:48px"></i>
                <p style="margin-top:12px;font-size:13px">Failed to load complaints: ${error.message}</p>
            </div>
        `;
    }
}

// === LOAD NEWS & TRENDING ===
function loadNews() {
    const container = document.getElementById('newsFeed');
    container.innerHTML = '';
    
    if (complaints.length === 0) {
        container.innerHTML = `<p style="font-size:12px;color:var(--text-light)">${LANGUAGE.no_complaints}</p>`;
        return;
    }
    
    complaints.slice(0, 5).forEach(c => {
        const item = document.createElement('div');
        item.className = 'news-item';
        const nameDisplay = (c.anonymous == '1' || c.anonymous == 1) ? LANGUAGE.anonymous : (c.student_name || LANGUAGE.user);
        item.innerHTML = `
            <h5>${escapeHtml(c.title).substring(0, 40)}${c.title.length > 40 ? '...' : ''}</h5>
            <p>
                <i class="bi bi-person-circle"></i>
                ${nameDisplay}
                <span>•</span>
                <span>${timeAgo(c.created_at)}</span>
            </p>
        `;
        container.appendChild(item);
    });
    loadTrending();
}

function loadTrending() {
    const container = document.getElementById('trendingIssues');
    if (complaints.length === 0) {
        container.innerHTML = `<p style="font-size:12px;color:var(--text-light)">${LANGUAGE.no_complaints}</p>`;
        return;
    }
    
    const counts = {};
    complaints.forEach(c => {
        counts[c.category] = (counts[c.category] || 0) + 1;
    });
    const trending = Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3)
        .map(([cat, count]) => ({ text: cat.charAt(0).toUpperCase() + cat.slice(1), count }));

    container.innerHTML = '';
    trending.forEach(t => {
        const item = document.createElement('div');
        item.className = 'news-item';
        item.innerHTML = `<h5>${t.text}</h5><p><i class="bi bi-bar-chart-fill"></i> ${t.count} ${LANGUAGE.complaints}</p>`;
        container.appendChild(item);
    });
}

// === LIKE, COMMENTS, SHARE ===
async function toggleLike(id, btn) {
    try {
        const response = await fetch('api/toggle_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ complaint_id: id })
        });
        
        const data = await response.json();
        if (data.success) {
            const span = btn.querySelector('span');
            const icon = btn.querySelector('i');
            const isLiked = btn.classList.contains('active');
            
            if (isLiked) {
                span.textContent = parseInt(span.textContent) - 1;
                btn.classList.remove('active');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            } else {
                span.textContent = parseInt(span.textContent) + 1;
                btn.classList.add('active');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            }
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        showToast('Error toggling like', 'error');
    }
}

async function toggleComments(id) {
    const panel = document.getElementById('comments-panel-' + id);
    if (panel.style.display === 'block') {
        panel.style.display = 'none';
    } else {
        panel.style.display = 'block';
        panel.innerHTML = `<div style="padding:10px;text-align:center;color:var(--text-light);font-size:12px"><i class="bi bi-hourglass-split"></i> ${LANGUAGE.loading}</div>`;
        
        try {
            const response = await fetch(`api/get_comments.php?complaint_id=${id}`);
            const data = await response.json();
            
            let commentsHtml = '';
            if (data.success && data.comments.length > 0) {
                data.comments.forEach(comment => {
                    commentsHtml += `
                        <div style="padding:10px;background:var(--bg);border-radius:8px;margin-bottom:6px">
                            <div style="font-weight:700;font-size:12px;margin-bottom:4px">${escapeHtml(comment.user_name)}</div>
                            <div style="font-size:12px;color:var(--text)">${escapeHtml(comment.content)}</div>
                            <div style="font-size:10px;color:var(--text-light);margin-top:4px">${timeAgo(comment.created_at)}</div>
                        </div>
                    `;
                });
            } else {
                commentsHtml = `
                    <div style="padding:10px;background:var(--bg);border-radius:8px;margin-bottom:6px">
                        <div style="font-weight:700;font-size:12px;margin-bottom:4px">System</div>
                        <div style="font-size:12px;color:var(--text)">${LANGUAGE.under_review}</div>
                        <div style="font-size:10px;color:var(--text-light);margin-top:4px">1h ago</div>
                    </div>
                `;
            }
            
            panel.innerHTML = `
                <div style="max-height:250px;overflow-y:auto;margin-bottom:10px;">
                    ${commentsHtml}
                </div>
                <form onsubmit="postComment(event, ${id})" style="display:flex;gap:6px">
                    <input name="content" placeholder="${LANGUAGE.comment_placeholder}" style="flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);font-size:12px" required>
                    <button class="btn-primary" style="padding:8px 12px"><i class="bi bi-send-fill"></i></button>
                </form>
            `;
        } catch (error) {
            console.error('Error loading comments:', error);
            panel.innerHTML = `<div style="padding:10px;text-align:center;color:var(--danger);font-size:12px">Error loading comments</div>`;
        }
    }
}

async function postComment(e, id) {
    e.preventDefault();
    const input = e.target.querySelector('input');
    const content = input.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch('api/post_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ complaint_id: id, content: content })
        });
        
        const data = await response.json();
        if (data.success) {
            showToast(LANGUAGE.comment_posted, 'success');
            input.value = '';
            // Refresh comments
            toggleComments(id);
        }
    } catch (error) {
        console.error('Error posting comment:', error);
        showToast('Error posting comment', 'error');
    }
}

function shareVia(platform) {
    const { id, title, desc } = currentShareData;
    const link = window.location.href.split('?')[0];
    const text = `${title} - ${desc}`;

    switch(platform) {
        case 'whatsapp': window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank'); break;
        case 'twitter': window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`, '_blank'); break;
        case 'facebook': window.open(`https://www.facebook.com/sharer/sharer.php?quote=${encodeURIComponent(text)}`, '_blank'); break;
        case 'copy': 
            navigator.clipboard.writeText(link).then(() => showToast(LANGUAGE.copy_link, 'success'));
            break;
        case 'email': window.location.href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(text)}`; break;
    }
    closeShareModal();
}

function blockUser() {
    if (confirm(LANGUAGE.block_confirm)) {
        showToast(LANGUAGE.user_blocked, 'success');
        closeShareModal();
    }
}

// === NOTIFICATIONS ===
function renderNotifications() {
    const container = document.getElementById('notificationsList');
    const notifs = <?php echo json_encode($notifications); ?>;
    if (!notifs.length) {
        container.innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--text-light)"><i class="bi bi-inbox" style="font-size:48px;opacity:0.3"></i><p style="margin-top:12px">No notifications</p></div>';
        return;
    }
    container.innerHTML = '';
    notifs.forEach(n => {
        const item = document.createElement('div');
        item.className = 'notification-item';
        const title = n.title || n.message.substring(0, 30) + (n.message.length > 30 ? '...' : '');
        const desc = n.title ? n.message : '';
        item.innerHTML = `
            <div class="notif-title">${escapeHtml(title)}</div>
            ${desc ? `<div class="notif-desc">${escapeHtml(desc)}</div>` : ''}
            <div class="notif-time">${timeAgo(n.created_at)}</div>
        `;
        container.appendChild(item);
    });
}

function checkNotifications() {
    const badge = document.getElementById('notifCount');
    if (USER.unread > 0) {
        badge.textContent = USER.unread > 99 ? '99+' : USER.unread;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// === PROFILE ===
async function saveProfile() {
    const name = document.getElementById('editName').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    
    if (!name) {
        showToast(LANGUAGE.login_required, 'error');
        return;
    }
    
    try {
        const response = await fetch('api/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, contact: phone })
        });
        
        const data = await response.json();
        if (data.success) {
            USER.name = name;
            USER.contact = phone;
            document.getElementById('userName').textContent = name;
            document.getElementById('profileName').textContent = name;
            showToast(LANGUAGE.profile_updated, 'success');
        } else {
            showToast(data.message || 'Update failed', 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showToast('Update failed', 'error');
    }
}

function logout() {
    if (confirm(LANGUAGE.logout_confirm)) {
        window.location.href = 'logout.php';
    }
}

// === UTILITIES ===
function timeAgo(dateStr) {
    const seconds = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (seconds < 60) return LANGUAGE.now;
    if (seconds < 3600) return Math.floor(seconds / 60) + LANGUAGE.minutes;
    if (seconds < 86400) return Math.floor(seconds / 3600) + LANGUAGE.hours;
    return Math.floor(seconds / 86400) + LANGUAGE.days;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed;bottom:90px;left:50%;transform:translateX(-50%);
        background:${type==='success'?'#10b981':type==='error'?'#ef4444':'#3B82F6'};
        color:white;padding:12px 20px;border-radius:10px;font-weight:600;font-size:13px;
        z-index:10000;box-shadow:0 10px 25px rgba(0,0,0,0.15);animation:slideUp .3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function scrollToFeed() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// === SECURITY QUESTIONS FUNCTIONS ===
async function loadExistingSecurityQuestions() {
    try {
        const response = await fetch('api/get_security_questions.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        const data = JSON.parse(text);
        
        if (data.success && data.questions) {
            data.questions.forEach((q, index) => {
                const questionSelect = document.getElementById(`security_question_${index + 1}`);
                const answerInput = document.getElementById(`security_answer_${index + 1}`);
                
                if (questionSelect && answerInput) {
                    questionSelect.value = q.question;
                    // Note: We don't pre-fill answers for security reasons
                    answerInput.value = '';
                }
            });
        } else {
            // No existing questions, clear all fields
            for(let i = 1; i <= 5; i++) {
                const questionSelect = document.getElementById(`security_question_${i}`);
                const answerInput = document.getElementById(`security_answer_${i}`);
                if (questionSelect && answerInput) {
                    questionSelect.value = '';
                    answerInput.value = '';
                }
            }
        }
    } catch (error) {
        console.error('Error loading security questions:', error);
        showToast('❌ ' + LANGUAGE.error_loading_security, 'error');
    }
}

async function saveSecurityQuestions(e) {
    e.preventDefault();
    const btn = document.getElementById('saveSecurityQuestionsBtn');
    const originalText = btn.innerHTML;
    
    try {
        // Validate all questions and answers
        const questions = [];
        let isValid = true;
        
        for(let i = 1; i <= 5; i++) {
            const question = document.getElementById(`security_question_${i}`).value.trim();
            const answer = document.getElementById(`security_answer_${i}`).value.trim();
            
            if (!question || !answer) {
                isValid = false;
                break;
            }
            
            questions.push({ question, answer });
        }
        
        if (!isValid) {
            showToast(LANGUAGE.please_fill_all_questions, 'error');
            return;
        }
        
        // Check for duplicate questions
        const questionSet = new Set();
        for(const q of questions) {
            if (questionSet.has(q.question)) {
                showToast(LANGUAGE.select_different_questions, 'error');
                return;
            }
            questionSet.add(q.question);
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + LANGUAGE.saving + '...';
        
        const formData = new FormData();
        formData.append('questions', JSON.stringify(questions));
        
        const response = await fetch('api/save_security_questions.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        const data = JSON.parse(text);
        
        if (data.success) {
            showToast('✅ ' + LANGUAGE.security_questions_saved, 'success');
            closeSecurityQuestionsModal();
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error saving security questions:', error);
        showToast('❌ ' + LANGUAGE.error_saving_security, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + LANGUAGE.save_security_questions;
    }
}

// === RATING FUNCTIONS ===
function selectRating(rating) {
    document.getElementById('rating_value').value = rating;
    
    const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    document.getElementById('ratingText').textContent = labels[rating];
    
    // Update star display
    document.querySelectorAll('.star-btn').forEach((btn, index) => {
        if (index < rating) {
            btn.classList.add('active');
            btn.querySelector('i').className = 'bi bi-star-fill';
        } else {
            btn.classList.remove('active');
            btn.querySelector('i').className = 'bi bi-star';
        }
    });
}

async function submitRating(e) {
    e.preventDefault();
    
    const btn = document.getElementById('ratingSubmitBtn');
    const originalText = btn.innerHTML;
    
    const formData = new FormData();
    formData.append('complaint_id', document.getElementById('rating_complaint_id').value);
    formData.append('rating', document.getElementById('rating_value').value);
    formData.append('response_time', document.getElementById('rating_response_time').value);
    formData.append('resolution_quality', document.getElementById('rating_resolution_quality').value);
    formData.append('would_recommend', document.getElementById('rating_would_recommend').checked ? 'true' : 'false');
    formData.append('feedback', document.getElementById('rating_feedback').value);
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + LANGUAGE.submitting + '...';
    
    try {
        const response = await fetch('api/submit_rating.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ ' + LANGUAGE.thank_you_feedback, 'success');
            closeRatingModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Rating error:', error);
        showToast('❌ ' + LANGUAGE.connection_error, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> ' + LANGUAGE.submit_rating;
    }
}

// === INITIALIZE ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    initPage();
});

// Check notifications every 30 seconds
setInterval(checkNotifications, 30000);
</script>
</body>
</html>