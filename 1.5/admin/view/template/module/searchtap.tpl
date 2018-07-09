<?php echo $header; ?>
<div id="content">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
            <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
        </div>
        <div class="content">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td><span class="required">*</span> <?php echo $collection_name; ?></td>
                        <td><input type="text" name="st_collection" value = '<?php echo $st_collection; ?>'>
                            <?php if ($error_code) { ?>
                            <span class="error"><?php echo $error_code; ?></span>
                            <?php } ?></td>
                    </tr>

                    <tr>
                        <td><span class="required">*</span> <?php echo $admin_key; ?></td>
                        <td><input type="text" name="st_admin_key" value = '<?php echo $st_admin_key; ?>'>
                            <?php if ($error_code) { ?>
                            <span class="error"><?php echo $error_code; ?></span>
                            <?php } ?></td>
                    </tr>

                    <tr>
                        <td><span class="required">*</span> <?php echo $search_key; ?></td>
                        <td><input type="text" name="st_search_key" value = '<?php echo $st_search_key; ?>'>
                            <?php if ($error_code) { ?>
                            <span class="error"><?php echo $error_code; ?></span>
                            <?php } ?></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>


<?php echo $footer; ?>