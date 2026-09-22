/**
 * 登录页脚本：GIF 验证码 + 提交登录、保存 Token（localStorage + Cookie）
 * 后续 AJAX 请求请在 Header 中携带 token 字段
 */
(function () {
    var btn = document.getElementById('login-btn');
    var errorBox = document.getElementById('login-error');
    var captchaImg = document.getElementById('captcha-img');
    var captchaInput = document.getElementById('captcha_code');

    var captchaIdent = '';

    function showError(msg) {
        errorBox.textContent = msg || '';
    }

    /* ------------------------------ GIF 验证码 ------------------------------ */
    var captchaLoading = false;

    function refreshCaptcha() {
        if (captchaLoading) {
            return;
        }
        captchaLoading = true;
        captchaIdent = '';

        fetch('/captcha/gif')
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('load failed');
                }
                var ident = res.headers.get('X-Captcha-Ident') || '';
                return res.blob().then(function (blob) {
                    captchaIdent = ident;
                    if (captchaImg.dataset.objectUrl) {
                        URL.revokeObjectURL(captchaImg.dataset.objectUrl);
                    }
                    captchaImg.dataset.objectUrl = URL.createObjectURL(blob);
                    captchaImg.src = captchaImg.dataset.objectUrl;
                });
            })
            .catch(function () {
                showError('验证码加载失败，请点击图片重试');
            })
            .finally(function () {
                captchaLoading = false;
            });
    }

    captchaImg.addEventListener('click', refreshCaptcha);

    /* ------------------------------ 登录 ------------------------------ */
    function saveToken(token) {
        // localStorage 供 AJAX 读取并写入 Header
        localStorage.setItem('token', token);
        // Cookie 兜底：页面跳转时随 Header 自动携带，保证服务端渲染页面可鉴权
        document.cookie = 'token=' + encodeURIComponent(token) + '; path=/; max-age=' + (7 * 24 * 3600) + '; SameSite=Lax';
    }

    function doLogin() {
        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;
        var captchaCode = captchaInput.value.trim();

        if (!username || !password) {
            showError('请输入用户名和密码');
            return;
        }
        if (!captchaCode) {
            showError('请输入验证码');
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
            body: JSON.stringify({
                username: username,
                password: password,
                captcha_ident: captchaIdent,
                captcha_code: captchaCode
            })
        }).then(function (res) {
            return res.json();
        }).then(function (ret) {
            if (ret.code === 0 && ret.data && ret.data.token) {
                saveToken(ret.data.token);
                window.location.href = '/panel';
                return;
            }
            showError(ret.msg || '登录失败，请稍后再试');
            if (ret.code === 2) {
                // 验证码错误或已过期，刷新重输
                captchaInput.value = '';
                refreshCaptcha();
            }
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
    captchaInput.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            doLogin();
        }
    });

    refreshCaptcha();
})();
