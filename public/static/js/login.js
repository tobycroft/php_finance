/**
 * 登录页脚本：提交登录、保存 Token（localStorage + Cookie）
 * 后续 AJAX 请求请在 Header 中携带 token 字段
 */
(function () {
    var btn = document.getElementById('login-btn');
    var errorBox = document.getElementById('login-error');

    function showError(msg) {
        errorBox.textContent = msg || '';
    }

    function saveToken(token) {
        // localStorage 供 AJAX 读取并写入 Header
        localStorage.setItem('token', token);
        // Cookie 兜底：页面跳转时随 Header 自动携带，保证服务端渲染页面可鉴权
        document.cookie = 'token=' + encodeURIComponent(token) + '; path=/; max-age=' + (7 * 24 * 3600) + '; SameSite=Lax';
    }

    function doLogin() {
        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;

        if (!username || !password) {
            showError('请输入用户名和密码');
            return;
        }

        btn.disabled = true;
        btn.textContent = '登录中...';

        fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ username: username, password: password })
        }).then(function (res) {
            return res.json();
        }).then(function (ret) {
            if (ret.code === 0 && ret.data && ret.data.token) {
                saveToken(ret.data.token);
                window.location.href = '/panel';
                return;
            }
            showError(ret.msg || '登录失败，请稍后再试');
        }).catch(function () {
            showError('网络异常，请稍后再试');
        }).finally(function () {
            btn.disabled = false;
            btn.textContent = '登 录';
        });
    }

    btn.addEventListener('click', doLogin);
    document.getElementById('password').addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            doLogin();
        }
    });
})();
