<?php

$cssFile = dirname(__FILE__) . '/email.css';
$css = is_file($cssFile) ? file_get_contents($cssFile) : '';

$title = isset($title) ? (string) $title : 'Portal de Aulas ETE';
$preheader = isset($preheader) ? (string) $preheader : '';
$heading = isset($heading) ? (string) $heading : '';
$content = isset($content) ? (string) $content : '';
$footer = isset($footer) ? (string) $footer : 'Escola Técnica Estadual — Portal de Aulas';

$safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$safePreheader = htmlspecialchars($preheader, ENT_QUOTES, 'UTF-8');
$safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
$safeFooter = htmlspecialchars($footer, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $safeTitle; ?></title>
  <style type="text/css">
<?php echo $css; ?>
  </style>
</head>
<body style="margin:0;padding:0;background:#eef2f8;color:#14233d;font-family:'Segoe UI',Arial,sans-serif;">
  <div class="email-preheader"><?php echo $safePreheader; ?></div>
  <table role="presentation" class="email-shell" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#eef2f8;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" class="email-card" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border:1px solid #d5deeb;border-radius:16px;">
          <tr>
            <td class="email-header" style="background:#0b2f6b;padding:22px 28px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="email-mark" style="background:#ffffff;color:#0b2f6b;font-size:18px;font-weight:800;letter-spacing:0.04em;border-radius:8px;padding:8px 10px;font-family:Arial,sans-serif;">ETE</td>
                  <td class="email-brand" style="color:#ffffff;font-size:18px;font-weight:700;padding-left:12px;font-family:Arial,sans-serif;">Portal de Aulas</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0;line-height:0;font-size:0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="email-stripe-y" width="34%" height="4" style="background:#f5c400;height:4px;line-height:4px;font-size:0;">&nbsp;</td>
                  <td class="email-stripe-g" width="33%" height="4" style="background:#1b7a3d;height:4px;line-height:4px;font-size:0;">&nbsp;</td>
                  <td class="email-stripe-r" width="33%" height="4" style="background:#c4122f;height:4px;line-height:4px;font-size:0;">&nbsp;</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="email-hero" style="background:#123a80;padding:28px 32px 24px;">
              <p class="email-kicker" style="margin:0 0 8px;color:#f5c400;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;font-family:Arial,sans-serif;">Escola Técnica Estadual</p>
              <h1 class="email-heading" style="margin:0;color:#ffffff;font-size:28px;line-height:1.2;font-weight:800;font-family:Arial,sans-serif;"><?php echo $safeHeading; ?></h1>
            </td>
          </tr>
          <tr>
            <td class="email-body" style="padding:28px 32px 8px;color:#14233d;font-size:16px;line-height:1.55;font-family:'Segoe UI',Arial,sans-serif;">
              <?php echo $content; ?>
            </td>
          </tr>
          <tr>
            <td class="email-footer" style="padding:8px 32px 28px;color:#5b6b86;font-size:13px;line-height:1.4;font-family:Arial,sans-serif;">
              <?php echo $safeFooter; ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
