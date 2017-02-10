<?php
session_start();
/**
 * @var $this Grid
 */
require 'Grid.php';
$Grid = new Grid('data/small.in');
$_SESSION ['grid'] = serialize($Grid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link href="https://afeld.github.io/emoji-css/emoji.css" rel="stylesheet">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <style type="text/css">
        .yellow {
            background-color: #ff0;
        }
        
        .green {
            background-color: #0f0;
        }
        
    </style>
    
</head>
<body>

<table cellpadding="2" cellspacing="2" border="0" id="grid">
    <?php for ($i = 0, $cntI = $Grid->R; $i < $cntI; $i++ ): ?>
        <tr>
            <?php for ($j = 0, $cntJ = $Grid->C; $j < $cntJ; $j++ ): ?>
                <td id="<?= 'cell_' . $i . '_' . $j ?>"><?= empty($Grid->dataAr [$i] [$j]) ? '<i class="em em-tomato"></i>' : '<i class="em em-mushroom"></i>'; ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; ?>
</table>

<script type="text/javascript">
    var a = 0;
    $(function(){
        var doStep = setInterval(function () {
            a++;
            // console.log(a);
            // if (a > 2)
                // clearInterval(doStep);
            $.ajax({
                async: false,
                url: 'google.php',
                method: 'post',
                dataType: 'json',
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus + ': ' + errorThrown);
                },
                success: function (data) {
                    // console.log(data);
                    if (data.stop == 1) {
                        clearInterval(doStep);
                    } else {
                        $('table#grid td').removeClass();
                        var dataGrid = data.grid;
                        // console.log(data.grid);
                        for ( var i in dataGrid ) {
                            for ( var j in dataGrid [i] ) {
                                if ( dataGrid [i] [j] == 1 ) {
                                    $('#cell_' + i + '_' + j).addClass('green');
                                } else if ( dataGrid [i] [j] == 2 ) {
                                    $('#cell_' + i + '_' + j).addClass('yellow');
                                }
                            }
                        }
                    }
                }
            });
        }, 100);
    });
</script>

</body>
</html>
    