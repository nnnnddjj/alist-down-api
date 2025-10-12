# AlistDown - 轻量级 Alist 下载代理工具

一个简单高效的 Alist API 下载代理，支持 Token 缓存和链接隐藏，让文件下载更安全便捷。

## ✨ 特性

- 🔐 **智能 Token 管理** - 自动缓存和刷新 Alist 登录 Token
- 🚀 **即装即用** - 简单配置，快速部署
- 🔗 **链接隐藏** - 保护真实文件路径，增强安全性
- ⚙️ **灵活配置** - 支持启用/禁用 Alist 登录功能
- 📱 **友好界面** - 提供美观的 Web 测试界面
- 🔄 **自动重试** - Token 失效时自动重新获取

## 🚀 快速开始

### 环境要求

- PHP 7.4 或更高版本
- cURL 扩展
- Alist 服务（可选，根据配置决定）

### 示例网页

https://www.u3022173.nyat.app:12046/alistapi/

### 使用说明：
1. 将api.php放在web文件夹的根目录下
2. 配置下面的参数：
``` // Alist配置参数
define('ALIST_LOGIN_ENABLED', true);  // 是否启用Alist登录功能
define('ALIST_URL', 'http://127.0.0.1:5244');  // Alist服务地址
define('ALIST_USERNAME', 'your_username');  // Alist用户名
define('ALIST_PASSWORD', 'your_password');  // Alist密码
```
3. 使用http://yourserver.com/api.php?file=/路径/文件名.zip 进行下载


