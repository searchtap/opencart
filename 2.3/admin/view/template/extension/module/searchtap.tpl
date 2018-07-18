
<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-first-module" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-first-module" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $collection_name; ?></label>
                        <div class="col-sm-10">
                            <?php if ($searchtap_collection) { ?>
                            <input type="text" name="searchtap_collection" value="<?php echo $searchtap_collection; ?>" id="st_collection" class="form-control" required>
                            <?php } else { ?>
                            <input type="text" name="searchtap_collection" id="st_collection" class="form-control" required>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-status"><?php echo $admin_key; ?></label>
                        <div class="col-sm-10">
                            <?php if ($searchtap_admin_key) { ?>
                            <input type="text" id="st_admin_key" name="searchtap_admin_key" value="<?php echo $searchtap_admin_key; ?>" class="form-control" required>
                            <?php } else { ?>
                            <input type="text" id="st_admin_key" name="searchtap_admin_key" class="form-control" required>
                            <?php } ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- merges the footer with the template -->
<?php echo $footer; ?>