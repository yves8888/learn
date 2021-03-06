<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>定位</title>
    <script src="../jquery.js">

    </script>
</head>
<body>
<style>
    #container{
        position:relative;
    }

    #div1{
        position:absolute;display:none;top:20px;left:65px;width:668px;height:148px;padding:15px;box-sizing:border-box;background:#fff;border:1px solid #e5e5e5
    }
</style>
<div id="container">
    <span id="look"> 查看说明</span>
    <div id="div1">
        层级关系的比较
        1. 对于同级元素，默认(或position:static)情况下文档流后面的元素会覆盖前面的。

        2. 对于同级元素，position不为static且z-index存在的情况下z-index大的元素会覆盖z-index小的元素，即z-index越大优先级越高。

        3. IE6/7下position不为static，且z-index不存在时z-index为0，除此之外的浏览器z-index为auto。

        4. z-index为auto的元素不参与层级关系的比较，由向上遍历至此且z-index不为auto的元素来参与比较。
    </div>
</div>

<script>
    $(function(){
        $('#look').hover(function(){
            $('#div1').show()
        },function(){
            $('#div1').hide()
        })
    })
</script>

</body>
</html>