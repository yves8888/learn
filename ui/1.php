<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="stylesheet" href="./layui/css/layui.css">
    <script src="../html/jquery-3.2.1.min.js"></script>
    <script src="./layui/layui.all.js"></script>
</head>
<body>

<style>

</style>

<pre>
    <div>
        <ul>
            <li>test</li>
        </ul>
    </div>
<ul>
    <li>HTML将不会被解析</li>
    <li>有木有感觉非常方便</li>
</ul>
<script>
    !function(){
        var a = 123;
    }();
</script>
</pre>
<script>
    layui.code({
        elem:"pre",
        title:'php代码',
        skin:"notepad",
        encode:true,
        about:false
    })
</script>
</body>
</html>