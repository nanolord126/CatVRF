import { ref, computed } from 'vue';

interface OnboardingState {
  step: number;
  inn: string;
  companyData: any;
  documents: Record<string, File>;
  selfiePhoto: File | null;
  verificationResult: any;
  isSubmitting: boolean;
}

export function useOnboarding() {
  const state = ref<OnboardingState>({
    step: 0,
    inn: '',
    companyData: null,
    documents: {},
    selfiePhoto: null,
    verificationResult: null,
    isSubmitting: false,
  });

  const steps = [
    { label: 'ИНН', description: 'Введите ИНН вашей организации' },
    { label: 'Документы', description: 'Загрузите выписку ЕГРЮЛ и паспорт' },
    { label: 'Верификация', description: 'Пройдите проверку личности' },
    { label: 'Модерация', description: 'Ожидайте подтверждения' },
  ];

  const currentStep = computed(() => state.value.step);
  const canProceed = computed(() => {
    switch (state.value.step) {
      case 0:
        return state.value.inn.length === 10 || state.value.inn.length === 12;
      case 1:
        return state.value.documents.egrul !== undefined;
      case 2:
        return state.value.verificationResult !== null;
      default:
        return false;
    }
  });

  const setStep = (step: number) => {
    state.value.step = step;
  };

  const nextStep = () => {
    if (canProceed.value && state.value.step < steps.length - 1) {
      state.value.step++;
    }
  };

  const prevStep = () => {
    if (state.value.step > 0) {
      state.value.step--;
    }
  };

  const setInn = (inn: string) => {
    state.value.inn = inn;
  };

  const setCompanyData = (data: any) => {
    state.value.companyData = data;
  };

  const addDocument = (type: string, file: File) => {
    state.value.documents[type] = file;
  };

  const setSelfiePhoto = (photo: File) => {
    state.value.selfiePhoto = photo;
  };

  const setVerificationResult = (result: any) => {
    state.value.verificationResult = result;
  };

  const setSubmitting = (isSubmitting: boolean) => {
    state.value.isSubmitting = isSubmitting;
  };

  const reset = () => {
    state.value = {
      step: 0,
      inn: '',
      companyData: null,
      documents: {},
      selfiePhoto: null,
      verificationResult: null,
      isSubmitting: false,
    };
  };

  return {
    state,
    steps,
    currentStep,
    canProceed,
    setStep,
    nextStep,
    prevStep,
    setInn,
    setCompanyData,
    addDocument,
    setSelfiePhoto,
    setVerificationResult,
    setSubmitting,
    reset,
  };
}
