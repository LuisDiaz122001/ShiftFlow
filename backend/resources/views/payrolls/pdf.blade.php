<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Nomina #{{ $payroll->id }}</title>
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
        .header table,
        .data-table {
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
        .data-table {
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        .data-table th {
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
                    <div style="font-size: 12px; color: #64748b;">Sistema de Gestion de Turnos y Nomina</div>
                </td>
                <td class="document-info">
                    <div class="document-title">COMPROBANTE DE NOMINA</div>
                    <div style="font-size: 14px; font-weight: bold;">#{{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="status-badge">{{ strtoupper($payroll->estado ?? 'pending') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Informacion del Empleado</div>
    <table class="data-table">
        <tr>
            <th>Nombre Completo:</th>
            <td>{{ $user->name }}</td>
            <th>Identificacion:</th>
            <td>{{ $employee->documento }}</td>
        </tr>
        <tr>
            <th>Correo:</th>
            <td>{{ $user->email }}</td>
            <th>Ciclo:</th>
            <td>{{ $cycle?->fecha_inicio?->format('d/m/Y') }} - {{ $cycle?->fecha_fin?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Fecha de Pago:</th>
            <td>{{ $payroll->fecha_pago?->format('d/m/Y') }}</td>
            <th>Version:</th>
            <td>{{ $payroll->version }}</td>
        </tr>
        <tr>
            <th>Total de horas:</th>
            <td>{{ number_format($payroll->total_hours ?? 0, 2, ',', '.') }} h</td>
            <th>Tarifa por hora:</th>
            <td>${{ number_format($payroll->hourly_rate ?? 0, 0, ',', '.') }} COP</td>
        </tr>
        <tr>
            <th>Total a pagar:</th>
            <td>${{ number_format($payroll->total_amount ?? $payroll->neto_pagado, 0, ',', '.') }} COP</td>
            <th>Estado:</th>
            <td>{{ strtoupper($payroll->estado ?? 'pending') }}</td>
        </tr>
    </table>

    <div class="section-title">Resumen de Liquidacion</div>
    <table class="data-table">
        <tbody>
            <tr>
                <td>Sueldo base proporcional</td>
                <td class="text-right">${{ number_format($payroll->salario_base_pagado, 0, ',', '.') }} COP</td>
            </tr>
            <tr>
                <td>Recargos pagados</td>
                <td class="text-right">${{ number_format($payroll->recargos_pagados, 0, ',', '.') }} COP</td>
            </tr>
            <tr>
                <td>Deduccion salud</td>
                <td class="text-right">${{ number_format($payroll->deduccion_salud, 0, ',', '.') }} COP</td>
            </tr>
            <tr>
                <td>Deduccion pension</td>
                <td class="text-right">${{ number_format($payroll->deduccion_pension, 0, ',', '.') }} COP</td>
            </tr>
            <tr class="total-row">
                <td>Total neto a pagar</td>
                <td class="text-right">${{ number_format($payroll->neto_pagado, 0, ',', '.') }} COP</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Detalle Contable</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Tipo</th>
                <th class="text-center">Horas</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $detail)
                <tr>
                    <td>{{ $detail->label }}</td>
                    <td>{{ strtoupper($detail->type) }}</td>
                    <td class="text-center">{{ $detail->hours ? number_format($detail->hours, 2) : '-' }}</td>
                    <td class="text-right">${{ number_format($detail->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Turnos del Ciclo</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th class="text-right">Total Turno</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shifts as $shift)
                <tr>
                    <td>{{ $shift->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $shift->fecha_inicio->format('H:i') }}</td>
                    <td>{{ $shift->fecha_fin->format('H:i') }}</td>
                    <td class="text-right">${{ number_format($shift->total_pago, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div style="font-size: 12px;">
            <strong>Snapshot:</strong> Este documento representa la liquidacion generada para el ciclo seleccionado.
        </div>
        <div style="margin-top: 10px; font-size: 11px;">
            <strong>Generado:</strong> {{ $generated_at->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="footer">
        Generado automaticamente por ShiftFlow el {{ $generated_at->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
