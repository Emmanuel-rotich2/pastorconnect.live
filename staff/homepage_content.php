<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();
$staff=staff($pdo);
$msg='';$error='';

$mission=setting($pdo,'homepage_mission','');
$vision=setting($pdo,'homepage_vision','');
$motto=setting($pdo,'homepage_motto','');
$theme=setting($pdo,'homepage_theme_year','');
$quote=setting($pdo,'homepage_daily_quote','');
$quoteImage=setting($pdo,'homepage_daily_quote_image','');
$quoteDate=setting($pdo,'homepage_daily_quote_date','');

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=(string)($_POST['action']??'');
    try{
        if($action==='save_identity'){
            $mission=trim((string)($_POST['mission']??''));
            $vision=trim((string)($_POST['vision']??''));
            $motto=trim((string)($_POST['motto']??''));
            $theme=trim((string)($_POST['theme_year']??''));
            if(mb_strlen($mission)>5000||mb_strlen($vision)>5000||mb_strlen($motto)>500||mb_strlen($theme)>300) throw new RuntimeException('Please keep the church information within the allowed length.');
            $q=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            foreach([
                'homepage_mission'=>$mission,
                'homepage_vision'=>$vision,
                'homepage_motto'=>$motto,
                'homepage_theme_year'=>$theme,
            ] as $k=>$v) $q->execute([$k,$v]);
            log_activity($pdo,null,(int)$staff['id'],'homepage_identity_updated','Updated the public homepage mission, vision, motto and theme of the year.');
            $msg='Church identity content saved and published to the homepage.';
        }elseif($action==='publish_quote'){
            $quote=trim((string)($_POST['daily_quote']??''));
            $quoteDate=trim((string)($_POST['quote_date']??date('Y-m-d')));
            if($quote==='' && empty($_FILES['quote_image']['name'])) throw new RuntimeException('Add a daily quote or upload a quote image before publishing.');
            if(mb_strlen($quote)>2000) throw new RuntimeException('The daily quote is too long. Please keep it under 2,000 characters.');
            $dt=DateTime::createFromFormat('Y-m-d',$quoteDate);
            if(!$dt||$dt->format('Y-m-d')!==$quoteDate) throw new RuntimeException('Please select a valid quote date.');

            $newImage=$quoteImage;
            if(isset($_FILES['quote_image']) && $_FILES['quote_image']['error']!==UPLOAD_ERR_NO_FILE){
                $file=$_FILES['quote_image'];
                if($file['error']!==UPLOAD_ERR_OK) throw new RuntimeException('The quote image could not be uploaded.');
                if((int)$file['size']>5*1024*1024) throw new RuntimeException('The quote image must be 5 MB or smaller.');
                $finfo=new finfo(FILEINFO_MIME_TYPE);
                $mime=$finfo->file($file['tmp_name']);
                $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if(!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WEBP quote images are allowed.');
                if(@getimagesize($file['tmp_name'])===false) throw new RuntimeException('The uploaded file is not a valid image.');
                $dir=__DIR__.'/../uploads/daily_quotes';
                if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('The quote upload directory could not be created.');
                $name='quote_'.bin2hex(random_bytes(12)).'.'.$allowed[$mime];
                if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('The quote image could not be saved.');
                $newImage='/uploads/daily_quotes/'.$name;
                if($quoteImage && str_starts_with($quoteImage,'/uploads/daily_quotes/')){
                    $old=__DIR__.'/..'.$quoteImage;
                    if(is_file($old)) @unlink($old);
                }
            }
            $q=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            foreach([
                'homepage_daily_quote'=>$quote,
                'homepage_daily_quote_image'=>$newImage,
                'homepage_daily_quote_date'=>$quoteDate,
            ] as $k=>$v) $q->execute([$k,$v]);
            $quoteImage=$newImage;
            log_activity($pdo,null,(int)$staff['id'],'homepage_daily_quote_published','Published a new daily quote to the public homepage for '.$quoteDate.'.');
            $msg='Daily quote published successfully to the homepage.';
        }elseif($action==='remove_quote_image'){
            if($quoteImage && str_starts_with($quoteImage,'/uploads/daily_quotes/')){
                $old=__DIR__.'/..'.$quoteImage;
                if(is_file($old)) @unlink($old);
            }
            $pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('homepage_daily_quote_image','') ON DUPLICATE KEY UPDATE setting_value='' ")->execute();
            $quoteImage='';
            log_activity($pdo,null,(int)$staff['id'],'homepage_daily_quote_image_removed','Removed the daily quote image from the public homepage.');
            $msg='Quote image removed. The written daily quote remains published.';
        }else{
            throw new RuntimeException('Unknown content action.');
        }
    }catch(Throwable $e){
        $error=$e instanceof RuntimeException?$e->getMessage():'The homepage content could not be saved.';
    }
}

$page_title='Homepage Content';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/staff_sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Homepage Content</h1><p>Control the message visitors see on the FGCK Joyland public homepage.</p></div></div>
 <a href="/" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>View Homepage</a>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?=e($error)?></div><?php endif;?>

<div class="message-hero mb-4">
 <div class="position-relative" style="z-index:1"><div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1px;color:#f0c866">Public homepage</div><h2>Lead the message your church shares with the world.</h2><p>Update the church mission, vision, motto, annual theme and daily inspiration. Saving here publishes the information to the public homepage without changing the church database structure.</p></div>
</div>

<div class="row g-4">
 <div class="col-xl-7">
  <div class="cardx p-4">
   <div class="d-flex justify-content-between align-items-start mb-4"><div><h3 class="section-title mb-1">Church identity</h3><p class="text-muted small mb-0">These values appear publicly on the homepage.</p></div><i class="bi bi-stars fs-3 text-warning"></i></div>
   <form method="post">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_identity">
    <div class="mb-3"><label class="form-label fw-semibold">Mission</label><textarea name="mission" class="form-control" rows="5" maxlength="5000" placeholder="Enter the church mission..."><?=e($mission)?></textarea></div>
    <div class="mb-3"><label class="form-label fw-semibold">Vision</label><textarea name="vision" class="form-control" rows="5" maxlength="5000" placeholder="Enter the church vision..."><?=e($vision)?></textarea></div>
    <div class="row g-3">
      <div class="col-md-7"><label class="form-label fw-semibold">Motto</label><input name="motto" class="form-control" maxlength="500" value="<?=e($motto)?>" placeholder="Enter the church motto"></div>
      <div class="col-md-5"><label class="form-label fw-semibold">Church Theme of the Year</label><input name="theme_year" class="form-control" maxlength="300" value="<?=e($theme)?>" placeholder="e.g. Year of Overflow"></div>
    </div>
    <button class="btn btn-primary mt-4"><i class="bi bi-cloud-arrow-up me-1"></i>Save & Publish to Homepage</button>
   </form>
  </div>
 </div>
 <div class="col-xl-5">
  <div class="cardx p-4">
   <div class="d-flex justify-content-between align-items-start mb-4"><div><h3 class="section-title mb-1">Daily Quote</h3><p class="text-muted small mb-0">Publish text, an image quote, or both.</p></div><i class="bi bi-quote fs-3 text-warning"></i></div>
   <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="publish_quote">
    <div class="mb-3"><label class="form-label fw-semibold">Quote</label><textarea name="daily_quote" class="form-control" rows="6" maxlength="2000" placeholder="Write today's inspirational quote..."><?=e($quote)?></textarea></div>
    <div class="mb-3"><label class="form-label fw-semibold">Quote Date</label><input type="date" name="quote_date" class="form-control" value="<?=e($quoteDate?:date('Y-m-d'))?>" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Upload Quote Image <span class="text-muted fw-normal">(optional)</span></label><input type="file" name="quote_image" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WEBP • maximum 5 MB. The uploaded image will be shown on the homepage.</div></div>
    <?php if($quoteImage):?><div class="border rounded-3 p-2 mb-3 bg-light"><img src="<?=e($quoteImage)?>" alt="Current daily quote" class="img-fluid rounded-2" style="max-height:220px;width:100%;object-fit:cover"><div class="d-flex justify-content-between align-items-center mt-2"><small class="text-muted">Current published image</small><button type="submit" form="removeQuoteImage" class="btn btn-sm btn-outline-danger">Remove image</button></div></div><?php endif;?>
    <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Publish Daily Quote to Homepage</button>
   </form>
   <?php if($quoteImage):?><form id="removeQuoteImage" method="post" class="d-none"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="remove_quote_image"></form><?php endif;?>
  </div>
 </div>
</div>

<div class="cardx p-4 mt-4">
 <div class="d-flex align-items-center gap-3"><div class="icon-box"><i class="bi bi-shield-check"></i></div><div><h3 class="section-title mb-1">Pastor-only publishing</h3><p class="text-muted small mb-0">Only an authenticated Pastor can change these homepage messages or upload the daily quote.</p></div></div>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
