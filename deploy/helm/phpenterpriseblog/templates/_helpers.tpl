{{- define "fb.name" -}}phpenterpriseblog{{- end -}}
{{- define "fb.labels" -}}
app.kubernetes.io/name: {{ include "fb.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}
