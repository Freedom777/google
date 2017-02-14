<?php
session_start();
/**
 * @var $this Grid
 */
require 'Grid.php';

$Grid = new Grid('data/small.in', 'data/small.out');
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
<div id="container">
    <div style="width:20%; float: left">
        <table cellpadding="2" cellspacing="2" border="0" id="grid">
            <?php for ($y = 0, $cntY = $Grid->R; $y < $cntY; $y++ ): ?>
                <tr>
                    <?php for ($x = 0, $cntJ = $Grid->C; $x < $cntJ; $x++ ): ?>
                        <td id="<?= 'cell_' . $y . '_' . $x ?>"><?= empty($Grid->dataAr [$y] [$x]) ? '<i class="em em-tomato"></i>' : '<i class="em em-mushroom"></i>'; ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
    <div style="width: 30%; float: left">
        <table cellpadding="2" cellspacing="2" border="0" id="stat">
            <tr>
                <td>
                    Mushrooms rest: <span id="mushrooms_cnt"></span>
                </td>
            </tr>
            <tr>
                <td>
                    Tomatoes rest: <span id="tomatoes_cnt"></span>
                </td>
            </tr>
            <tr>
                <td>
                    TOTAL rest: <span id="rest_cnt"></span>
                </td>
            </tr>
            <tr>
                <td>
                    Rollbacks count: <span id="rollbacks_cnt"></span>
                </td>
            </tr>
            <tr>
                <td>
                    Total steps: <span id="steps_cnt"></span>
                </td>
            </tr>
            <tr>
                <td>
                    Current slice: <span id="current_slice"></span>
                </td>
            </tr>
            <tr>
                <td>
                    Current path: <span id="current_path"></span>
                </td>
            </tr>
        </table>
    </div>

    <div style="width: 40%;float: left">
        <table cellpadding="2" cellspacing="2" border="3" id="stat">
            <?php foreach ( $Grid->availSliceCombinations as $idx => $availSliceCombination ): ?>
            <tr>
                <td>
                    Index: <?= $idx ?>
                </td>
                <td>
                    Slice: <?= $availSliceCombination [0] . ' x ' . $availSliceCombination [1] ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

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

                        $('#mushrooms_cnt').text(data.rest.mushrooms);
                        $('#tomatoes_cnt').text(data.rest.tomatoes);
                        $('#rest_cnt').text(data.rest.total);
                        $('#rollbacks_cnt').text(data.rollbacks_cnt);
                        $('#current_slice').text(data.current_slice);
                        $('#steps_cnt').text(data.steps_cnt);
                        $('#current_path').text(data.path);
                    }
                }
            });
        }, 5000);
    });
</script>

</body>
</html>