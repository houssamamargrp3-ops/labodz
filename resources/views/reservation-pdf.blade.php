<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Confirmation de Rendez-vous - Labo El Meniaa</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24pt;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header p {
            color: #7f8c8d;
            font-size: 11pt;
            margin: 5px 0;
        }

        .doc-title {
            background-color: #3498db;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 20px 0;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #3498db;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 8px 0;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #2c3e50;
            width: 200px;
        }

        .value {
            color: #333;
        }

        table.analysis-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.analysis-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #3498db;
            font-size: 10pt;
            text-transform: uppercase;
        }

        table.analysis-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 10pt;
        }

        .price-col {
            text-align: right;
            font-weight: bold;
        }

        .total-container {
            margin-top: 20px;
            text-align: right;
        }

        .total-box {
            display: inline-block;
            background-color: #2c3e50;
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
        }

        .total-label {
            font-size: 12pt;
            margin-right: 15px;
        }

        .total-amount {
            font-size: 16pt;
            font-weight: bold;
        }

        .instructions-box {
            background-color: #fffde7;
            border: 1px solid #fff59d;
            padding: 15px;
            margin-top: 10px;
            border-radius: 4px;
        }

        .instructions-title {
            color: #f9a825;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 11pt;
        }

        .important-notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 10pt;
        }

        .important-notes h3 {
            margin-top: 0;
            font-size: 11pt;
            color: #2c3e50;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 9pt;
            color: #95a5a6;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .footer p {
            margin: 3px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LABORATOIRE EL MENIAA</h1>
        <p>Expertise en Analyses Médicales & Diagnostics</p>
        <p>Cité de l'Indépendance, El Meniaa, Algérie</p>
    </div>

    <div class="doc-title">
        CONFIRMATION DE RENDEZ-VOUS
    </div>

    <div class="section">
        <div class="section-title">INFORMATIONS DU PATIENT</div>
        <table class="info-table">
            <tr>
                <td class="label">Nom Complet :</td>
                <td class="value">{{ $reservation->name }}</td>
            </tr>
            <tr>
                <td class="label">Téléphone :</td>
                <td class="value">{{ $reservation->phone }}</td>
            </tr>
            @if($reservation->email)
            <tr>
                <td class="label">E-mail :</td>
                <td class="value">{{ $reservation->email }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Sexe :</td>
                <td class="value">{{ $reservation->gender == 'male' ? 'Masculin' : 'Féminin' }}</td>
            </tr>
            <tr>
                <td class="label">Date de Naissance :</td>
                <td class="value">{{ \Carbon\Carbon::parse($reservation->birth_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Date du Rendez-vous :</td>
                <td class="value">{{ \Carbon\Carbon::parse($reservation->date)->format('d/m/Y') }} à {{ $reservation->time }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">ANALYSES DEMANDÉES</div>
        <table class="analysis-table">
            <thead>
                <tr>
                    <th style="width: 50%">Désignation de l'Analyse</th>
                    <th style="width: 25%">Délai de Résultat</th>
                    <th style="width: 25%; text-align: right;">Prix Unitaire</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($reservation->analyses as $analysis)
                <tr>
                    <td><strong>{{ $analysis->name_fr }}</strong></td>
                    <td>{{ str_replace(['ساعة', 'ساعات', 'يوم', 'أيام'], ['heure(s)', 'heure(s)', 'jour(s)', 'jour(s)'], $analysis->duration) }}</td>
                    <td class="price-col">{{ number_format($analysis->price, 2) }} DA</td>
                </tr>
                @php $total += $analysis->price; @endphp
                @endforeach
            </tbody>
        </table>

        <div class="total-container">
            <div class="total-box">
                <span class="total-label">MONTANT TOTAL :</span>
                <span class="total-amount">{{ number_format($total, 2) }} DA</span>
            </div>
        </div>
    </div>

    @php
    $hasPreparation = $reservation->analyses->filter(function($analysis) {
        return !empty($analysis->preparation_fr);
    })->count() > 0;
    @endphp

    @if($hasPreparation)
    <div class="section">
        <div class="section-title">CONSIGNES DE PRÉPARATION</div>
        <div class="instructions-box">
            <div class="instructions-title">Important : Veuillez suivre ces instructions pour garantir la précision des résultats</div>
            @foreach($reservation->analyses as $analysis)
            @if($analysis->preparation_fr)
            <p style="margin: 8px 0;"><strong>- {{ $analysis->name_fr }} :</strong> {{ $analysis->preparation_fr }}</p>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="important-notes">
        <h3>REMARQUES IMPORTANTES</h3>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Ce document est une confirmation de demande de rendez-vous.</li>
            <li>Veuillez vous présenter au laboratoire 15 minutes avant l'heure fixée.</li>
            <li>En cas d'empêchement, merci de nous prévenir au moins 24 heures à l'avance.</li>
            <li>N'oubliez pas d'apporter votre pièce d'identité et ce document.</li>
        </ul>
    </div>

    <div class="footer">
        <p><strong>Adresse :</strong> Cité de l'Indépendance, El Meniaa | <strong>Tel :</strong> 0550 12 34 56</p>
        <p><strong>E-mail :</strong> info@labo-elmeniaa.dz | <strong>Horaires :</strong> 08:00 - 18:00</p>
        <p style="margin-top: 15px;">Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>

</html>