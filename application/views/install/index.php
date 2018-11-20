
<?php include_once ('__header.php'); ?>


<block name="header">
    <li class="active"><a href="javascript:;">安装协议</a></li>
    <li><a href="javascript:;">环境检测</a></li>
    <li><a href="javascript:;">创建数据库</a></li>
    <li><a href="javascript:;">安装</a></li>
    <li><a href="javascript:;">完成</a></li>
</block>

<block name="body">
    <h1>EP~FO Client API V1.1 安装协议</h1>
    <p>版权所有 (c) 2017~2018，深圳早付鸟信息科技有限公司保留所有权利。</p>

    <p>用户须知：本协议是您与早付鸟公司之间关于您使用EP~FO平台及服务的法律协议。</p>
</block>

<block name="footer">
    <a class="btn btn-primary btn-large" href="<?php echo $base_url.'Install/step1'; ?>">同意安装协议</a>
    <a class="btn btn-large" href="javascript:;">不同意</a>
</block>

<?php include_once ('__footer.php'); ?>