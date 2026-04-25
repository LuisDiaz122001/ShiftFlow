<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Nómina #{{ $payroll->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .document-info {
            text-align: right;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .section-title {
            background-color: #f1f5f9;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.data-table th, table.data-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        table.data-table th {
            color: #64748b;
            font-weight: bold;
        }
        .summary-box {
            margin-top: 20px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
        }
        .total-row {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #e2e8f0;
        }
        .footer {
            margin-top: 40px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo">ShiftFlow</div>
                    <div style="font-size: 12px; color: #64748b;">Sistema de Gestión de Turnos y Nómina</div>
                </td>
                <td class="document-info">
                    <div class="document-title">COMPROBANTE DE NÓMINA</div>
                    <div style="font-size: 14px; font-weight: bold;">#{{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="status-badge">CERRADO / INMUTABLE</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Información del Empleado</div>
    <table class="data-table">
        <tr>
            <th>Nombre Completo:</th>
            <td>{{ $user->name }}</td>
            <th>Identificación:</th>
            <td>{{ $employee->documento }}</td>
        </tr>
        <tr>
            <th>Correo:</th>
            <td>{{ $user->email }}</td>
            <th>Periodo:</th>
            <td>{{ $payroll->fecha_inicio->format('d/m/Y') }} - {{ $payroll->fecha_fin->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Resumen de Horas y Liquidación</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-center">Cantidad (Horas)</th>
                <th class="text-right">Valor Liquidado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Jornada Diurna (Ordinaria + Extras)</td>
                <td class="text-center">{{ $payroll->diurnas_hours }}h</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Jornada Nocturna (Ordinaria + Extras)</td>
                <td class="text-center">{{ $payroll->nocturnas_hours }}h</td>
                <td class="text-right">-</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL NETO A PAGAR</td>
                <td class="text-center">{{ $payroll->total_hours }}h</td>
                <td class="text-right">${{ number_format($payroll->total_pago, 0, ',', '.') }} COP</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Detalle de Turnos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th class="text-center">Horas</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shifts as $shift)
            <tr>
                <td>{{ $shift->fecha_inicio->format('d/m/Y') }}</td>
                <td>{{ $shift->fecha_inicio->format('H:i') }}</td>
                <td>{{ $shift->fecha_fin->format('H:i') }}</td>
                <td class="text-center">{{ $shift->total_hours }}h</td>
                <td class="text-right">${{ number_format($shift->total_pago, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div style="font-size: 12px;">
            <strong>Nota Legal:</strong> Este documento representa un snapshot contable inmutable generado por el sistema ShiftFlow. 
            Cualquier modificación en los turnos operativos después de la fecha de cierre no afectará este comprobante.
        </div>
        <div style="margin-top: 10px; font-size: 11px;">
            <strong>Fecha de Cierre Contable:</strong> {{ $payroll->closed_at->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="footer">
        Generado automáticamente por ShiftFlow el {{ $generated_at->format('d/m/Y H:i:s') }}<br>
        ShiftFlow - Optimización y Transparencia en la Gestión de Nómina
    </div>
</body>
</html>
