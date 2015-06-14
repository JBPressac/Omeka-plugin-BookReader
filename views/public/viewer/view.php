<?php
    $title = metadata($item, array('Dublin Core', 'Title'));
    if ($creator = metadata($item, array('Dublin Core', 'Creator'))) {
        $title .= ' - ' . $creator;
    }
    $title = BookReader::htmlCharacter($title);

    if (!$bookreader->itemLeafsCount()) {
        echo '<html><head></head><body>';
        echo __('This item has no viewable files.');
        echo '</body></html>';
        return;
    }

//    $coverFile = $bookreader->getCoverFile();

    list($pageIndexes, $pageNumbers, $pageLabels, $imgWidths, $imgHeights) = $bookreader->imagesData();

    $server = preg_replace('#^https?://#', '', WEB_ROOT);
    $serverFullText = $server . '/book-reader/index/fulltext';
    $sharedUrl = WEB_PLUGIN . '/BookReader/views/shared';
    $imgDir = WEB_PLUGIN . '/BookReader/views/shared/images/';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html <?php echo ($ui == 'embed') ? 'id="embedded" ' : ''; ?>lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1.0" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <base target="_parent" />
<!--    <link rel="apple-touch-icon" href="<?php // echo $coverFile->getWebPath('thumbnail'); ?>" /> -->
    <link rel="shortcut icon" href="<?php echo get_option('bookreader_favicon_url'); ?>" type="image/x-icon" />
    <title><?php echo $title; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $sharedUrl . '/css/BookReader.css'; ?>" />
    <?php if ($custom_css = get_option('bookreader_custom_css')): ?>
    <link rel="stylesheet" href="<?php echo url($custom_css); ?>" />
    <?php endif; ?>
    <!-- JavaScripts -->
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery-1.4.2.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery-ui-1.8.5.custom.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/dragscrollable.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.colorbox-min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.ui.ipad.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.bt.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/BookReader.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/ToCmenu.js'; ?>" charset="utf-8"></script>
</head>
<body>
    <div></div>
    <div id="BookReader">
        <br />
        <noscript>
            <p>
                The BookReader requires JavaScript to be enabled. Please check that your browser supports JavaScript and that it is enabled in the browser settings.
            </p>
        </noscript>
    </div>

    <script type="text/javascript">
// This file shows the minimum you need to provide to BookReader to display a book
//
// Copyright (c) 2008-2009 Internet Archive. Software license AGPL version 3.

// Create the BookReader object via spreadsheet data instead of Omeka database.

imagesArray = [];
function spreadsheetLoaded(json) {
    imagesArray = [];

    json = json.substring(json.indexOf("(") + 1);
    json = json.substring(0, json.lastIndexOf(")"));
    json = JSON.parse(json);

    parts = json["feed"]["entry"];

    for (n in parts) {
        if (parts[n]["gsx$image"] != undefined) {
            imagesArray.push({
                "image": parts[n]["gsx$image"]["$t"],
                "height": parts[n]["gsx$height"]["$t"],
                "width": parts[n]["gsx$width"]["$t"]
            });
        }
    }
    var externalArray = [];
    var htmlstring = "";

    // Read-aloud and search need backend compenents and are not supported in
    // the demo.
    br = new BookReader();
    br.imagesBaseURL = <?php echo json_encode($imgDir); ?>;
    br.pageNums = [];
    br.pageLabels = [];

    // Return the width of a given page, else we assume all images are 800
    // pixels wide.
    br.getPageWidth = function(index) {
        if ((imagesArray[index]) && (imagesArray[index].width != undefined)) {
            return parseInt(imagesArray[index].width);
        } else {
            return 800;
        }
    }

    // Return the height of a given page, else we assume all images are 1200
    // pixels high.
    br.getPageHeight = function(index) {
        if ((imagesArray[index]) && (imagesArray[index].height != undefined)) {
            return parseInt(imagesArray[index].height)
        } else {
            return 1200;
        }
    }

    // TODO Bug if page num starts with a "n" (rarely used as page number).
    // This is used only to build the url to a specific page.
    br.getPageNum = function(index) {
        var pageNum = this.pageNums[index];
        if (pageNum && pageNum != 'null') {
            return pageNum;
        }
        var pageLabel = this.pageLabels[index];
        if (pageLabel) {
            return pageLabel;
        }
        // Accessible index starts at 0 so we add 1 to make human.
        index++;
        return 'n' + index;
    }

    br.getPageLabel = function(index) {
        var pageLabel = this.pageLabels[index];
        if (pageLabel) {
            return pageLabel;
        }
        var pageNum = this.pageNums[index];
        if (pageNum) {
            return '<?php echo html_escape(__('Page')); ?> ' + pageNum;
        }
        // Accessible index starts at 0 so we add 1 to make human.
        index++;
        return 'n' + index;
    }

    // This is used only to get the page num from the url of a specific page.
    // This is needed because the hash can be the number or the label.
    // Practically, it does a simple check of the page hash.
    br.getPageNumFromHash = function(pageHash) {
        // Check if this is a page number.
        for (var index = 0; index < this.pageNums.length; index++) {
            if (this.pageNums[index] == pageHash) {
                return pageHash;
            }
        }
        // Check if this is a page label.
        for (var index = 0; index < this.pageLabels.length; index++) {
            if (this.pageLabels[index] == pageHash) {
                return pageHash;
            }
        }
        // Check if this is an index.
        if (pageHash.slice(0,1) == 'n') {
            var pageIndex = pageHash.slice(1, pageHash.length);
            // Index starts at 0 so we make it internal.
            pageIndex = parseInt(pageIndex) - 1;
            if (this.getPageNum(pageIndex) == pageHash) {
                return pageHash;
            }
        }
        return undefined;
    }

    // We load the images from archive.org.
    // You can modify this function to retrieve images using a different URL
    // structure.
    br.getPageURI = function(index, reduce, rotate) {
        // reduce and rotate are ignored in this simple implementation, but we
        // could e.g. look at reduce and load images from a different directory
        // or pass the information to an image server
        // var leafStr = '0000';
        // var imgStr = (index+1).toString();
        // var re = new RegExp("0{"+imgStr.length+"}$");
        // var url =
        // 'http://www.archive.org/download/BookReader/img/page'+leafStr.replace(re,
        // imgStr) + '.jpg';

        url = imagesArray[index].image;

        return url;
    }

    // Return which side, left or right, that a given page should be displayed
    // on.
    br.getPageSide = function(index) {
        if (0 == (index & 0x1)) {
            return 'R';
        } else {
            return 'L';
        }
    }

    // This function returns the left and right indices for the user-visible
    // spread that contains the given index. The return values may be
    // null if there is no facing page or the index is invalid.
    br.getSpreadIndices = function(pindex) {
        var spreadIndices = [ null, null ];
        if ('rl' == this.pageProgression) {
            // Right to Left
            if (this.getPageSide(pindex) == 'R') {
                spreadIndices[1] = pindex;
                spreadIndices[0] = pindex + 1;
            } else {
                // Given index was LHS
                spreadIndices[0] = pindex;
                spreadIndices[1] = pindex - 1;
            }
        } else {
            // Left to right
            if (this.getPageSide(pindex) == 'L') {
                spreadIndices[0] = pindex;
                spreadIndices[1] = pindex + 1;
            } else {
                // Given index was RHS
                spreadIndices[1] = pindex;
                spreadIndices[0] = pindex - 1;
            }
        }
        return spreadIndices;
    }

    // For a given "accessible page index" return the page number in the book.
    //
    // For example, index 5 might correspond to "Page 1" if there is front
    // matter such as a title page and table of contents.
    br.getPageNum = function(index) {
        return index + 1;
    }

    // Total number of leafs
    br.numLeafs = imagesArray.length;

    // Book title and the URL used for the book title link
    br.bookTitle = json["feed"]["title"]["$t"];
    br.bookUrl = "proba" //BookReaderConfig.bookUrl;

    // Override the path used to find UI images
    // br.imagesBaseURL = '../BookReader/images/';

    br.getEmbedCode = function(frameWidth, frameHeight, viewParams) {
        return "Embed code not supported in bookreader demo.";
    }

    br.initUIStrings = function() {
        var titles = {
            '.logo': <?php echo json_encode(__('Go to %s', option('site_title'))); ?>, // $$$ update after getting OL record
            '.zoom_in': <?php echo json_encode(__('Zoom in')); ?>,
            '.zoom_out': <?php echo json_encode(__('Zoom out')); ?>,
            '.onepg': <?php echo json_encode(__('One-page view')); ?>,
            '.twopg': <?php echo json_encode(__('Two-page view')); ?>,
            '.thumb': <?php echo json_encode(__('Thumbnail view')); ?>,
            '.print': <?php echo json_encode(__('Print this page')); ?>,
            '.embed': <?php echo json_encode(__('Embed BookReader')); ?>,
            '.link': <?php echo json_encode(__('Link to this document and page')); ?>,
            '.bookmark': <?php echo json_encode(__('Bookmark this page')); ?>,
            '.read': <?php echo json_encode(__('Read this document aloud')); ?>,
            '.share': <?php echo json_encode(__('Share this document')); ?>,
            '.info': <?php echo json_encode(__('About this document')); ?>,
            '.full': <?php echo json_encode(__('Show fullscreen')); ?>,
            '.book_left': <?php echo json_encode(__('Flip left')); ?>,
            '.book_right': <?php echo json_encode(__('Flip right')); ?>,
            '.book_up': <?php echo json_encode(__('Page up')); ?>,
            '.book_down': <?php echo json_encode(__('Page down')); ?>,
            '.play': <?php echo json_encode(__('Play')); ?>,
            '.pause': <?php echo json_encode(__('Pause')); ?>,
            '.BRdn': <?php echo json_encode(__('Show/hide nav bar')); ?>, // Would have to keep updating on state change to have just "Hide nav bar"
            '.BRup': <?php echo json_encode(__('Show/hide nav bar')); ?>,
            '.book_top': <?php echo json_encode(__('First page')); ?>,
            '.book_bottom': <?php echo json_encode(__('Last page')); ?>
        };
        if ('rl' == this.pageProgression) {
            titles['.book_leftmost'] = <?php echo json_encode(__('Last page')); ?>;
            titles['.book_rightmost'] = <?php echo json_encode(__('First page')); ?>;
        } else { // LTR
            titles['.book_leftmost'] = <?php echo json_encode(__('First page')); ?>;
            titles['.book_rightmost'] = <?php echo json_encode(__('Last page')); ?>;
        }

        for (var icon in titles) {
            if (titles.hasOwnProperty(icon)) {
                $('#BookReader').find(icon).attr('title', titles[icon]);
            }
        }
    }

    br.i18n = function(msg) {
        var msgs = {
            'View':<?php echo json_encode(__('View')); ?>,
            'Search results will appear below...':<?php echo json_encode(__('Search results will appear below...')); ?>,
            'No matches were found.':<?php echo json_encode(__('No matches were found.')); ?>,
            "This book hasn't been indexed for searching yet. We've just started indexing it, so search should be available soon. Please try again later. Thanks!":<?php echo json_encode(__("This book hasn't been indexed for searching yet. We've just started indexing it, so search should be available soon. Please try again later. Thanks!")); ?>,
            'Embed Bookreader':<?php echo json_encode(__('Embed Bookreader')); ?>,
            'The bookreader uses iframes for embedding. It will not work on web hosts that block iframes. The embed feature has been tested on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.':<?php echo json_encode(__('The bookreader uses iframes for embedding. It will not work on web hosts that block iframes. The embed feature has been tested on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.')); ?>,
            'Close':<?php echo json_encode(__('Close')); ?>,
            'Add a bookmark':<?php echo json_encode(__('Add a bookmark')); ?>,
            'You can add a bookmark to any page in any book. If you elect to make your bookmark public, other readers will be able to see it.':<?php echo json_encode(__('You can add a bookmark to any page in any book. If you elect to make your bookmark public, other readers will be able to see it.')); ?>,
            'You must be logged in to your <a href="">Open Library account</a> to add bookmarks.':<?php echo json_encode(__('You must be logged in to your <a href="">Open Library account</a> to add bookmarks.')); ?>,
            'Make this bookmark public.':<?php echo json_encode(__('Make this bookmark public.')); ?>,
            'Keep this bookmark private.':<?php echo json_encode(__('Keep this bookmark private.')); ?>,
            'Add a bookmark':<?php echo json_encode(__('Add a bookmark')); ?>,
            'Search result':<?php echo json_encode(__('Search result')); ?>,
            'Search inside':<?php echo json_encode(__('Search inside')); ?>,
            'GO':<?php echo json_encode(__('GO')); ?>,
            "Go to this book's page on Open Library":<?php echo json_encode(__("Go to this book's page on Open Library")); ?>,
            'Loading audio...':<?php echo json_encode(__('Loading audio...')); ?>,
            'Could not load soundManager2, possibly due to FlashBlock. Audio playback is disabled':<?php echo json_encode(__('Could not load soundManager2, possibly due to FlashBlock. Audio playback is disabled')); ?>,
            'About this book':<?php echo json_encode(__('About this book')); ?>,
            'About the BookReader':<?php echo json_encode(__('About the BookReader')); ?>,
            'Copy and paste one of these options to share this book elsewhere.':<?php echo json_encode(__('Copy and paste one of these options to share this book elsewhere.')); ?>,
            'Link to this page view:':<?php echo json_encode(__('Link to this page view:')); ?>,
            'Link to the book:':<?php echo json_encode(__('Link to the book:')); ?>,
            'Embed a mini Book Reader:':<?php echo json_encode(__('Embed a mini Book Reader:')); ?>,
            '1 page':<?php echo json_encode(__('1 page')); ?>,
            '2 pages':<?php echo json_encode(__('2 pages')); ?>,
            'Open to this page?':<?php echo json_encode(__('Open to this page?')); ?>,
            'NOTE:':<?php echo json_encode(__('NOTE:')); ?>,
            "We've tested EMBED on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.":<?php echo json_encode(__("We've tested EMBED on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.")); ?>,
            'Finished':<?php echo json_encode(__('Finished')); ?>
        };
        return msgs[msg];
    }

    // Let's go!
    br.init();

    $('#BRtoolbar').find('.read').hide();
    $('#BRreturn').html($('#BRreturn').text());

<?php
        // Si jamais la recherche n'est pas disponible (pas de fichier XML), on
        // va masquer les éléments permettant de la lancer (SMA 201210)
        if (!$bookreader->hasDataForSearch()): ?>
    $('#textSrch').hide();
    $('#btnSrch').hide();
        <?php endif;
?>
    return;
}

function loadData() {
    var dataurl = 'https://spreadsheets.google.com/feeds/list/'
        + '0Ag7PrlWT3aWadDdVODJLVUs0a1AtUVlUWlhnXzdwcGc'
        + '/od6/public/values?alt=json-in-script&callback=spreadsheetLoaded';
    $.ajax({
        url: dataurl,
        dataType: 'jsonP',
        jsonpCallback: "spreadsheetLoaded",
        success: function(data) {
            spreadsheetLoaded(data);
        }
    });
}

$(document).ready(function() {
    key = window.location.search.split("key=")[0];
    console.log(window.location + "; " + key);
    loadData();
});
    </script>

<?php
    // Table of Contents if exist, plugin PdfToc required.
    echo fire_plugin_hook('toc_for_bookreader', array(
         'view' => $this,
         'item' => $item,
    ));
?>
    </body>
</html>