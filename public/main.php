<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TJP 1.1.5</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <meta name="author" content="Tom Walder">
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        body {
            margin-top: 10px;
        }
        td.route span.glyphicon-chevron-right,
        #fromto span.glyphicon-chevron-right {
            font-size: 0.7em;
        }
        #header a.pull-right {
            margin-left: 10px;
        }
        #settings_input {
            display: none;
        }
        #settings_input .well {
            margin: 10px 0 10px 0;
        }
        #settings_input .well .col-xs-4 {
            padding-left: 5px;
            padding-right: 5px;
        }
        #settings_form {
            display: inline-block;
        }
        #settings_form .form-group {
            margin-bottom: 0;
        }
.platform {
    font-size: 20px;
}
    </style>
</head>
<body>
<div class="container">
    <div class="row" id="header">
        <div class="col-xs-12">
            <a href="/" class="btn btn-default" id="btn_there">There and back again</a>
            <a href="/" id="reload" class="btn btn-default pull-right"><span class="glyphicon glyphicon-refresh"></span></a>
            <a href="/" id="settings" class="btn btn-default pull-right"><span class="glyphicon glyphicon-cog"></span></a>
        </div>
    </div>
    <div class="row" id="settings_input">
        <div class="col-xs-12">
            <div class="well">
                <form class="form-inline" id="settings_form">
                    <div clas="row">
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="setting_from" class="sr-only">From</label>
                                <input type="text" class="form-control" id="setting_from" placeholder="WML" value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : 'WML'; ?>">
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="setting_to" class="sr-only">To</label>
                                <input type="text" class="form-control" id="setting_to" placeholder="MAN" value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : 'MAN'; ?>">
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th id="fromto"></th>
                    <th class="text-right">Route</th>
                </tr></thead>
                <tbody id="results">
                <tr><td colspan="2">Loading..</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script>
var tjp = {};
tjp.fail = function(msg){
    alert(msg);
};
tjp.reload = function(){
    tjp.go();
};
tjp.toggleSettings = function(){
    tjp.settings_row.toggle();
};
tjp.swap = function(){
    var from = tjp.from_input.val();
    var to = tjp.to_input.val();
    tjp.from_input.val(to);
    tjp.to_input.val(from);
};
tjp.go = function(){
    tjp.results.css('opacity', '0.5');
    $.post('/data', {
        'from': tjp.from_input.val(),
        'to': tjp.to_input.val()
    })
    .done(function(data){
        if(data && data.success) {
            console.log(data);
            tjp.results.empty();

            tjp.from_input.val(data.from);
            tjp.to_input.val(data.to);
            tjp.fromto.html(data.from + ' <span class="glyphicon glyphicon-chevron-right"></span> ' + data.to);

            for(s in data.services) {
                var service = data.services[s];

                var departure = service.std;
                var delay = service.ontime ? '' : (' <strong>' + service.etd + '</strong>');

                var duration = ', ' + service.duration + 'm';

                var platform = '<span class="pull-right platform">' + (service.platform ? service.platform : '-') + '</span>';

                var stops = service.stops ? (service.stops + ' stops') : '';

                tjp.results.append('<tr><td>' + departure + delay + duration + platform + '<br />' + stops + '' + '</td><td class="text-right text-muted route">' + service.operator + '<br />' + service.origin_crs + ' <span class="glyphicon glyphicon-chevron-right"></span> ' + service.dest_crs + '</td></tr>');
            }

            tjp.results.css('opacity', '1.0');
        } else {
            tjp.fail('Server fail');
        }
    })
    .fail(function(xhr){
        tjp.fail('Request fail');
    })
    .always(function(data){
        // anything?
    });
};
$(document).ready(function(){

    tjp.results = $('tbody#results');
    tjp.from_input = $('#setting_from');
    tjp.to_input = $('#setting_to');
    tjp.settings_row = $('#settings_input');
    tjp.fromto = $('#fromto');

    $('#reload').click(function(event){
        event.preventDefault();
        tjp.reload();
    });

    $('#settings').click(function(event){
        event.preventDefault();
        tjp.toggleSettings();
    });

    $('#settings_form').submit(function(event){
        event.preventDefault();
        tjp.toggleSettings();
        tjp.go();
    });

    $('#btn_there').click(function(event){
        event.preventDefault();
        tjp.swap();
        tjp.go();
    });

    $('#btn_back').click(function(event){
        event.preventDefault();
        tjp.swap();
        tjp.go();
    });

    tjp.go();

});
</script>
</body>
</html>