FROM python:3.9

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir --upgrade -r requirements.txt

COPY app.py .

EXPOSE 9006

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "9006"]
