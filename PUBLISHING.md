# Publishing Checklist

Use this checklist to publish `josemodi97/yii2-safaricom-daraja` to Packagist and the Yii extension directory.

## 1. GitHub

- Repository: `https://github.com/JoseModi97/yii2-safaricom-daraja`
- Default branch: `main`
- Package type: `yii2-extension`
- Current package name: `josemodi97/yii2-safaricom-daraja`

Before publishing, confirm the latest commit and tag are pushed:

```bash
git status
git push origin main
git push origin v1.0.2
```

## 2. Packagist

1. Log in to `https://packagist.org/`.
2. Choose `Submit`.
3. Enter the GitHub repository URL:

```text
https://github.com/JoseModi97/yii2-safaricom-daraja
```

4. Submit the package.
5. Confirm Packagist shows this install command:

```bash
composer require josemodi97/yii2-safaricom-daraja
```

6. Enable GitHub service hook or Packagist GitHub sync so future tags update automatically.

## 3. Yii Extension Directory

1. Log in at `https://www.yiiframework.com/login`.
2. Open `https://www.yiiframework.com/extensions/create`.
3. Create the extension using the Packagist package name:

```text
josemodi97/yii2-safaricom-daraja
```

Suggested metadata:

- Name: `josemodi97/yii2-safaricom-daraja`
- Category: `Web Service`
- Tags: `yii2`, `daraja`, `mpesa`, `safaricom`, `api-client`, `mobile-money`
- Summary: `Yii2 component for Safaricom Daraja, M-Pesa, Ratiba, B2B, B2C, C2B, STK Push, Pull Transactions, Lipa na Bonga, IMSI/SWAP, and IoT SIM portal API requests.`

## 4. After Publishing

- Open the Yii extension page and confirm the README renders correctly.
- Run `composer require josemodi97/yii2-safaricom-daraja` in a clean Yii2 project.
- Confirm the package appears under Yii 2.0 extensions.
