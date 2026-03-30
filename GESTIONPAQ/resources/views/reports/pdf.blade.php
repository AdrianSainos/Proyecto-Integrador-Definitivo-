<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte GESTIONPAQ</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      color: #1d2433;
      margin: 28px;
      font-size: 12px;
    }

    .header {
      margin-bottom: 24px;
      padding: 18px 20px;
      border-radius: 16px;
      background: linear-gradient(135deg, #0f7b6c 0%, #0b5d52 100%);
      color: #ffffff;
    }

    .eyebrow {
      text-transform: uppercase;
      letter-spacing: 0.16em;
      font-size: 10px;
      opacity: 0.75;
    }

    h1,
    h2,
    p {
      margin: 0;
    }

    .subtitle {
      margin-top: 8px;
      opacity: 0.88;
    }

    .meta {
      margin-top: 10px;
      font-size: 11px;
      opacity: 0.9;
    }

    .grid {
      width: 100%;
      border-collapse: separate;
      border-spacing: 10px;
      margin: 0 -10px 18px;
    }

    .card {
      width: 50%;
      vertical-align: top;
      padding: 14px;
      border-radius: 14px;
      background: #f6faf9;
      border: 1px solid #d9ebe8;
    }

    .card-label {
      color: #5d6880;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }

    .card-value {
      font-size: 20px;
      font-weight: bold;
      margin-top: 8px;
      color: #0b5d52;
    }

    .card-detail {
      margin-top: 6px;
      color: #536079;
      font-size: 11px;
    }

    .section-title {
      margin: 18px 0 10px;
      font-size: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    th,
    td {
      padding: 10px 12px;
      border-bottom: 1px solid #dbe5ea;
      text-align: left;
    }

    th {
      background: #f3f6f8;
      color: #536079;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
  </style>
</head>
<body>
  <section class="header">
    <div class="eyebrow">GESTIONPAQ</div>
    <h1>Reporte operativo</h1>
    <p class="subtitle">{{ $report['range']['label'] }} - {{ $report['range']['from'] }} a {{ $report['range']['to'] }}</p>
    <p class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</p>
  </section>

  <table class="grid">
    <tr>
      @foreach ($report['cards'] as $index => $card)
        <td class="card">
          <div class="card-label">{{ $card['title'] }}</div>
          <div class="card-value">{{ $card['value'] }}</div>
          <div class="card-detail">{{ $card['detail'] }}</div>
        </td>
        @if ($index % 2 === 1)
          </tr><tr>
        @endif
      @endforeach
    </tr>
  </table>

  <h2 class="section-title">Indicadores del periodo</h2>

  <table>
    <thead>
      <tr>
        <th>Indicador</th>
        <th>Valor</th>
        <th>Variacion</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($report['rows'] as $row)
        <tr>
          <td>{{ $row['metric'] }}</td>
          <td>{{ $row['value'] }}</td>
          <td>{{ $row['variation'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
