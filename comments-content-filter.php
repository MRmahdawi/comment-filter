<?php
/*
Plugin Name: فیلتر کلمات دیدگاه
Description: این افزونه ساده کلمات به کار رفته در دیدگاه کاربر را بررسی و سپس در صورت وجود آن کلمه از انتشار دیدگاه جلوگیری می کند. 
Version: 1.0
Author: عبدالرحمان مهدوی
*/

// فعالسازی افزونه
register_activation_hook(__FILE__, 'fwf_activation');
function fwf_activation() {
    global $wpdb;
    
    // ساخت جدول برای ذخیره کلمات ممنوعه
    $table_name = $wpdb->prefix . 'forbidden_words';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        word varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// منوی مدیریت افزونه
add_action('admin_menu', 'fwf_admin_menu');
function fwf_admin_menu() {
    add_menu_page('کلمات ممنوعه', 'کلمات ممنوعه', 'manage_options', 'forbidden-words', 'fwf_admin_page');
}

// صفحه مدیریت افزونه
function fwf_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'forbidden_words';
    
    if ($_POST['submit']) {
        // اضافه کردن کلمه ممنوعه
        $word = sanitize_text_field($_POST['word']);
        if (!empty($word)) {
            $wpdb->insert($table_name, array('word' => $word));
        }
    } elseif ($_POST['delete']) {
        // حذف کلمه ممنوعه
        $id = intval($_POST['delete']);
        $wpdb->delete($table_name, array('id' => $id));
    }
    
    $forbidden_words = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>مدیریت کلمات ممنوعه</h1>
        <form method="post">
            <input type="text" name="word" placeholder="کلمه مورد نظر را اضافه کنید" required>
            <input type="submit" name="submit" value="اضافه کردن">
        </form>

        <h2>کلمه ممنوعه یافت وجود ندارد</h2>
        <ul>
            <?php foreach ($forbidden_words as $word): ?>
                <li><?php echo esc_html($word->word); ?> 
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="delete" value="<?php echo $word->id; ?>">
                        <input type="submit" value="حذف">
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

// چک کردن کامنت
add_filter('preprocess_comment', 'fwf_check_comment');
function fwf_check_comment($commentdata) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'forbidden_words';
    $forbidden_words = $wpdb->get_col("SELECT word FROM $table_name");
    
    foreach ($forbidden_words as $word) {
        if (stripos($commentdata['comment_content'], $word) !== false) {
            wp_die('<div id="response">در نظر شما کلمات ممنوعه یافت شد. لطفاً متن نظرتان را اصلاح کنید.</div>');
        }
    }

    return $commentdata;
}
?>
