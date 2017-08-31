<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo isset($tpl_subtitle) ? $tpl_subtitle : ''; ?>{title}</title>
        <meta http-equiv="content-type" content="text/xhtml; charset=UTF-8" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <meta http-equiv="Content-Language" content="{lang}" />
        <meta http-equiv="gallerimg" content="no" />
        <meta http-equiv="pragma" content="no-cache" />
        <meta http-equiv="cache-control" content="no-cache" />

        <link rel="SHORTCUT ICON" href="favicon.ico">
            <link rel="stylesheet" type="text/css" href="tpl/stdstyle/css/main.css" />

        <?php foreach( $view->getLocalCss() as $css ) { ?>
          <link rel="stylesheet" type="text/css" href="<?=$css?>">
        <?php } //foreach-css ?>

            {htmlheaders}

        <?php
            if( $view->isGoogleAnalyticsEnabled() ){
                $view->googleAnalyticsChunk( $view->getGoogleAnalyticsKey() );
            }

            if( $view->isjQueryEnabled()){
                $view->callChunk('jQuery');
            }

            if( $view->isjQueryUIEnabled()){
                $view->callChunk('jQueryUI');
            }
            if( $view->isTimepickerEnabled()){
                $view->callChunk('timepicker');
            }
            if( $view->isLightBoxEnabled()){
                $view->callChunk('lightBoxLoader', true, false);
            }
        ?>
    </head>
    <body{bodyMod}>
        <div id="content">
            {template}
        </div>
        
        <?php
        		// lightbox js should be loaded at th end of page
            if( $view->isLightBoxEnabled()){
                $view->callChunk('lightBoxLoader', false, true);
            }
        ?>
    </body>
</html>