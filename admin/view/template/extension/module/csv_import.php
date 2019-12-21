<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">       
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>      
    </div>
  </div>

  <div class="container-fluid">
    <?php if (isset($error_warning) && !empty($error_warning)) { ?>
    <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if (isset($success) && !empty($success)) { ?>
    <div class="alert alert-success">
      <i class="fa fa-exclamation-circle"></i>
      <?php echo $success; ?>
      <button class="close" data-dismiss="alert" type="button">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" class="form-horizontal">          

          <div class="col-lg-5 сol-md-5 col-sm-10">
              <div class="form-group" style="padding: 20px">
              <label for="extension">Выберете формат</label>
              <select class="form-control" name="extension">
                  <option value="csv">.CSV</option>
              </select>
            </div>
          </div>
          
          <div class="col-lg-5 сol-md-5 col-sm-10">
              <div class="form-group">
              <label class="control-label" for="upload_file">Загрузить файл</label>
              <input type="file" name="upload_file" style="display:inline-block;margin:10px">
              <button type="submit" class="btn btn-primary">Загрузить</button>
            </div>
          </div>

        </form>

      </div>
    </div>




<?php echo $footer; ?>
