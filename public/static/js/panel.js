/**
 * 面板公共脚本：退出登录、侧边栏收起
 * 供所有面板页面共用
 */
(function () {
    function getToken() {
        return localStorage.getItem('token') || '';
    }

    function clearToken() {
        localStorage.removeItem('token');
        document.cookie = 'token=; path=/; max-age=0; SameSite=Lax';
    }

    var logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            if (!window.confirm('确定要退出登录吗？')) {
                return;
            }
            fetch('/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'token': getToken()
                }
            }).catch(function () {}).finally(function () {
                clearToken();
                window.location.href = '/login';
            });
        });
    }

    var toggle = document.getElementById('sidebar-toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
})();
